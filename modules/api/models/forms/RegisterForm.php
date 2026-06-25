<?php

namespace app\modules\api\models\forms;

use app\models\User;
use app\models\UserAccessToken;
use Exception;
use Yii;
use yii\base\Model;

class RegisterForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $password_confirmation;

    public function rules()
    {
        return [
            [['username', 'email', 'password', 'password_confirmation'], 'required'],
            [['email'], 'email'],
            [['username'], 'string', 'min' => 3, 'max' => 255],
            [['password'], 'string', 'min' => 6, 'max' => 255],
            [['password_confirmation'], 'compare', 'compareAttribute' => 'password'],
            [['username'], 'unique', 'targetClass' => User::class],
            [['email'], 'unique', 'targetClass' => User::class],
        ];
    }


    public function register()
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->generateVerificationToken();
            $user->status = User::STATUS_INACTIVE;

            if ($user->save()) {
                $auth = Yii::$app->authManager;
                $readerRole = $auth->getRole(User::ROLE_READER);
                if ($readerRole) {
                    $auth->assign($readerRole, $user->id);
                }
                try {
                    $verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['api/auth/verify-email', 't' => $user->verification_token]);
                    Yii::$app->mailer->compose(
                        ['html' => 'verify-email-html'],
                        ['user' => $user, 'verifyLink' => $verifyLink]
                    )
                        ->setFrom([$_ENV['SMTP_USERNAME'] => 'Yii2 Blog Support'])
                        ->setTo($user->email)
                        ->setSubject('Xác thực tài khoản - Yii2 Blog')
                        ->send();
                } catch (\Exception $e) {
                    Yii::error('Không thể gửi mail xác thực: ' . $e->getMessage(), 'mailer');
                }
                $transaction->commit();
                return $user;
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $this->addError('register', Yii::t('app', 'Registration failed.') . $e->getMessage());
        }

        return null;
    }
}
