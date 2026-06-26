<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\Category;
use app\models\UserAccessToken;
use Yii;

final class CategoryCest
{
    private string $token;
    private ?int $categoryId = null;

    public function _before(ApiTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $accessToken = new UserAccessToken();
        $accessToken->user_id = $user->id;
        $accessToken->token = Yii::$app->security->generateRandomString(64);
        $accessToken->expires_at = time() + 3600;
        $accessToken->save(false);
        $this->token = $accessToken->token;
    }

    public function _after(ApiTester $I): void
    {
        if ($this->categoryId) {
            $category = Category::findOne($this->categoryId);
            if ($category) {
                $category->delete();
            }
        }
    }

    public function testCategoryCRUD(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra bảo mật và các thao tác CRUD danh mục');

        // 1. Chặn khách vãng lai gọi API (Phải trả về 401)
        $I->sendGet('/api/categories');
        $I->seeResponseCodeIs(401);

        // 2. Đăng nhập và tạo mới Danh mục (POST /api/categories)
        $I->amBearerAuthenticated($this->token);
        $I->sendPost('/api/categories', [
            'name' => 'Danh mục kiểm thử',
        ]);
        $I->seeResponseCodeIs(200);

        $response = json_decode($I->grabResponse(), true);
        $this->categoryId = $response['data']['id'] ?? null;
        $I->assertNotNull($this->categoryId);

        // 3. Sửa danh mục (PUT /api/categories/<id>)
        $I->sendPut("/api/categories/{$this->categoryId}", [
            'name' => 'Danh mục kiểm thử đã sửa',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'data' => [
                'name' => 'Danh mục kiểm thử đã sửa'
            ]
        ]);

        // 4. Xóa danh mục (DELETE /api/categories/<id>)
        $I->sendDelete("/api/categories/{$this->categoryId}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Category deleted successfully.'
        ]);
    }
}
