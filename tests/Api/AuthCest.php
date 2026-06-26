<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\UserAccessToken;
use Yii;

final class AuthCest
{
    private string $testUsername = 'api_test_user';
    private string $testEmail = 'api_test_user@example.com';
    private string $testPassword = 'password123';

    // Xóa sạch user test cũ nếu còn tồn tại trước mỗi lần chạy test
    public function _before(ApiTester $I): void
    {
        $user = User::findOne(['username' => $this->testUsername]);
        if ($user) {
            UserAccessToken::deleteAll(['user_id' => $user->id]);
            $user->delete();
        }
    }

    // Dọn dẹp dữ liệu sau khi chạy test xong
    public function _after(ApiTester $I): void
    {
        $user = User::findOne(['username' => $this->testUsername]);
        if ($user) {
            UserAccessToken::deleteAll(['user_id' => $user->id]);
            $user->delete();
        }
    }

    public function testFullAuthFlow(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra luồng đăng ký, xác thực mail, đăng nhập, lấy profile và đăng xuất');

        // 1. Đăng ký tài khoản mới (POST /api/auth/register)
        $I->sendPost('/api/auth/register', [
            'username' => $this->testUsername,
            'email' => $this->testEmail,
            'password' => $this->testPassword,
            'password_confirmation' => $this->testPassword,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Registration successful. Please check your email to verify your account.'
        ]);

        // Lấy thông tin user vừa tạo từ Database để lấy verification_token
        $user = User::findOne(['username' => $this->testUsername]);
        $I->assertNotNull($user);
        $I->assertEquals(User::STATUS_INACTIVE, $user->status);
        $I->assertNotEmpty($user->verification_token);

        // 2. Kích hoạt tài khoản qua link mail (GET /api/auth/verify-email)
        $I->sendGet('/api/auth/verify-email', ['token' => $user->verification_token]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Your email has been verified successfully. You can now login.'
        ]);

        // Xác nhận trạng thái user đã đổi sang ACTIVE (1)
        $user->refresh();
        $I->assertEquals(User::STATUS_ACTIVE, $user->status);
        $I->assertNull($user->verification_token);

        // 3. Đăng nhập (POST /api/auth/login)
        $I->sendPost('/api/auth/login', [
            'username' => $this->testUsername,
            'password' => $this->testPassword,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        // Lấy Access Token từ response
        $response = json_decode($I->grabResponse(), true);
        $token = $response['data']['access_token'] ?? null;
        $I->assertNotEmpty($token);

        // 4. Gọi API Lấy thông tin cá nhân dùng Bearer Token (GET /api/auth/me)
        $I->amBearerAuthenticated($token);
        $I->sendGet('/api/auth/me');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'data' => [
                'username' => $this->testUsername,
                'email' => $this->testEmail
            ]
        ]);

        // 5. Đăng xuất (POST /api/auth/logout)
        $I->sendPost('/api/auth/logout');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Logout successfully.'
        ]);
    }
}
