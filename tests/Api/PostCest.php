<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;
use app\models\User;
use app\models\Post;
use app\models\PostLike;
use app\models\Category;
use app\models\UserAccessToken;
use Yii;

final class PostCest
{
    private string $token;
    private ?User $user = null;
    private ?int $postId = null;
    private ?int $categoryId = null;

    public function _before(ApiTester $I): void
    {
        $this->user = User::findOne(['username' => 'admin']);

        if (!$this->user) {
            // Nếu chưa seed dữ liệu, tự động tạo một user admin để test
            $this->user = new User();
            $this->user->username = 'admin_tester';
            $this->user->email = 'admin_tester@example.com';
            $this->user->setPassword('admin123');
            $this->user->generateAuthKey();
            $this->user->status = User::STATUS_ACTIVE;
            $this->user->save(false);

            $auth = Yii::$app->authManager;
            $role = $auth->getRole('admin');
            if ($role) {
                $auth->assign($role, $this->user->id);
            }
        }

        // Tạo một Danh mục tạm thời cho bài viết test
        $category = new Category();
        $category->name = 'Danh mục test bài viết';
        $category->slug = 'danh-muc-test-bai-viet';
        $category->save(false);
        $this->categoryId = $category->id;

        // Tạo access token cho user để phục vụ gọi API cần xác thực
        $accessToken = new UserAccessToken();
        $accessToken->user_id = $this->user->id;
        $accessToken->token = Yii::$app->security->generateRandomString(64);
        $accessToken->expires_at = time() + 3600;
        $accessToken->save(false);
        $this->token = $accessToken->token;
    }

    public function _after(ApiTester $I): void
    {
        // Xóa cứng post test sau khi chạy xong
        if ($this->postId) {
            PostLike::deleteAll(['post_id' => $this->postId]);
            $post = Post::findOne($this->postId);
            if ($post) {
                $post->delete();
            }
        }

        // Xóa danh mục tạm thời
        if ($this->categoryId) {
            $category = Category::findOne($this->categoryId);
            if ($category) {
                $category->delete();
            }
        }

        $testUser = User::findOne(['username' => 'admin_tester']);
        if ($testUser) {
            UserAccessToken::deleteAll(['user_id' => $testUser->id]);
            $testUser->delete();
        }
    }

    public function testPostOperations(ApiTester $I): void
    {
        $I->wantTo('Kiểm tra xem danh sách bài viết, tạo mới, like và xóa bài viết');

        // 1. Khách vãng lai xem danh sách bài viết công khai (GET /api/posts)
        $I->sendGet('/api/posts');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['status' => 'success']);

        // 2. Tạo bài viết mới (POST /api/posts) với Bearer Token
        $I->amBearerAuthenticated($this->token);
        $I->sendPost('/api/posts', [
            'title' => 'Bài viết kiểm thử tự động',
            'content' => 'Nội dung bài viết dùng để test API bằng Codeception.',
            'category_id' => $this->categoryId,
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $I->seeResponseCodeIs(200);
        $response = json_decode($I->grabResponse(), true);
        $this->postId = $response['data']['id'] ?? null;
        $I->assertNotNull($this->postId);

        // 3. Thích bài viết (POST /api/posts/<id>/like)
        $I->sendPost("/api/posts/{$this->postId}/like");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Post liked successfully.',
            'data' => true
        ]);

        // 4. Bỏ thích bài viết (POST /api/posts/<id>/like lần 2)
        $I->sendPost("/api/posts/{$this->postId}/like");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Post unliked successfully.',
            'data' => false
        ]);

        // 5. Xóa bài viết (DELETE /api/posts/<id>)
        $I->sendDelete("/api/posts/{$this->postId}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Post deleted successfully.'
        ]);
    }
}
