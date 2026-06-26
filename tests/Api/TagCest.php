<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\Tag;
use app\models\UserAccessToken;
use Yii;

final class TagCest
{
    private string $token;
    private ?int $tagId = null;

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
        if ($this->tagId) {
            $tag = Tag::findOne($this->tagId);
            if ($tag) {
                $tag->delete();
            }
        }
    }

    public function testTagCRUD(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra các thao tác CRUD Tag');

        // 1. Chặn khách vãng lai gọi API
        $I->sendGet('/api/tags');
        $I->seeResponseCodeIs(401);

        // 2. Tạo mới nhãn (POST /api/tags)
        $I->amBearerAuthenticated($this->token);
        $I->sendPost('/api/tags', [
            'name' => 'Nhãn kiểm thử',
        ]);
        $I->seeResponseCodeIs(201); // Coded in TagController: HTTP_CREATED (201)

        $response = json_decode($I->grabResponse(), true);
        $this->tagId = $response['data']['id'] ?? null;
        $I->assertNotNull($this->tagId);

        // 3. Sửa nhãn (PUT /api/tags/<id>)
        $I->sendPut("/api/tags/{$this->tagId}", [
            'name' => 'Nhãn kiểm thử đã sửa',
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'data' => [
                'name' => 'nhãn kiểm thử đã sửa'
            ]
        ]);

        // 4. Xóa nhãn (DELETE /api/tags/<id>)
        $I->sendDelete("/api/tags/{$this->tagId}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Tag deleted successfully.'
        ]);
    }
}
