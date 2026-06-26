<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\UserAccessToken;
use Yii;

final class AiCest
{
    private string $token;

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

    public function testAiGenerateTitle(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra gọi AI tạo gợi ý tiêu đề từ nội dung bài viết');

        $I->amBearerAuthenticated($this->token);
        $I->sendPost('/api/ai/generate-title', [
            'description' => 'Yii2 framework is a highly secure, fast, and feature-rich PHP framework.'
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => 'success']);

        $I->seeResponseMatchesJsonType([
            'data' => [
                'titles' => 'array'
            ]
        ]);
    }
}
