<?php

declare(strict_types=1);

namespace app\models;

use app\models\base\BaseUser;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\web\IdentityInterface;

class User extends BaseUser implements IdentityInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const ROLE_ADMIN = 'admin';
    const ROLE_AUTHOR = 'author';
    const ROLE_READER = 'reader';

    private $_currentToken;

    public function getCurrentToken()
    {
        return $this->_currentToken;
    }

    public function setCurrentToken($value)
    {
        $this->_currentToken = $value;
    }


    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]]
        ]);
    }

    public function fields()
    {
        return [
            'id',
            'username',
            'email',
            'status',
            'created_at',
            'roles',
        ];
    }

    public static function findIdentity($id): ?IdentityInterface
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $accessToken = UserAccessToken::find()
            ->where(['token' => $token])
            ->andWhere(['or', ['>', 'expires_at', time()], ['expires_at' => null]])
            ->andWhere(['revoked_at' => null])
            ->one();
        if ($accessToken) {
            $user = static::findOne(['id' => $accessToken->user_id, 'status' => self::STATUS_ACTIVE]);
            if ($user){
                $user->currentToken = $accessToken;
                return $user;
            }
        }
        return null;
    }

    public function getId()
    {
        return $this->id;
    }
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public static function findByUsername(string $username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }
    public function setPassword(string $password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
    public function validatePassword(string $password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    public function generateVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    public static function findByVerificationToken(string $token): ?self
    {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE,
        ]);
    }
    public function getRoles()
    {
        $auth = Yii::$app->authManager;
        return array_keys($auth->getRolesByUser($this->id));
    }
}
