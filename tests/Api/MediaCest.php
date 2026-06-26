<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\UserAccessToken;
use Yii;

final class MediaCest
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

        // Tạo một tệp ảnh giả lập trong thư mục tests/_data
        $dataDir = Yii::getAlias('@app/tests/_data');
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        // Tạo ảnh JPEG thật 1x1 bằng GD để vượt qua kiểm tra mime-type
        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(1, 1);
            imagejpeg($img, $dataDir . '/test.jpg');
            imagedestroy($img);
        } else {
            file_put_contents($dataDir . '/test.jpg', 'fake image content');
        }
    }

    public function testUploadMedia(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra chức năng tải file ảnh lên Cloudflare R2');

        $I->amBearerAuthenticated($this->token);

        // Thực hiện gửi tệp tin giả lập qua API dưới dạng mảng files
        $I->sendPost('/api/media/upload', [], [
            'files' => [
                Yii::getAlias('@app/tests/_data/test.jpg')
            ]
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => 'success']);

        // Kiểm tra định dạng dữ liệu trả về
        $I->seeResponseMatchesJsonType([
            'data' => [
                'media' => 'array'
            ]
        ]);
    }
}
