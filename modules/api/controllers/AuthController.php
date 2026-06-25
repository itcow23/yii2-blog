<?php

namespace app\modules\api\controllers;

use app\modules\api\models\forms\LoginForm;
use app\modules\api\models\forms\RegisterForm;
use Yii;

class AuthController extends BaseController
{
    public function behaviors()
    {
        $behaviors =  parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'register' => ['POST'],
                'login' => ['POST'],
                'logout' => ['POST'],
                'me' => ['GET'],
                'verify-email' => ['GET'],
            ],
        ];
        $behaviors['authenticator']['optional'] = ['register', 'login', 'verify-email'];
        return $behaviors;
    }

    public function actionRegister()
    {
        $form = new RegisterForm();
        $form->load(Yii::$app->request->post(), '');
        $user = $form->register();
        if ($user) {
            return [
                'message' => Yii::t('app', 'Registration successful. Please check your email to verify your account.'),
                'user' => $user
            ];
        }
        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return [
            'errors' => $form->errors
        ];
    }

    public function actionLogin()
    {
        $form = new LoginForm();
        $form->load(Yii::$app->request->post(), '');
        $user = $form->login();
        if ($user) {
            return [
                'user' => $user,
                'access_token' => $user->currentToken->token,
            ];
        }

        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return [
            'errors' => $form->errors
        ];
    }

    public function actionLogout()
    {
        $user = Yii::$app->user->identity;

        if ($user->currentToken) {
            $user->currentToken->updateAttributes([
                'revoked_at' => time(),
            ]);
        }

        return [
            'message' => Yii::t('app', 'Logout successfully.'),
        ];
    }

    public function actionMe()
    {
        return Yii::$app->user->identity;
    }

    public function actionVerifyEmail($token)
    {
        if (empty($token) || !is_string($token)) {
            Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
            return [
                'errors' => [
                    'token' => [Yii::t('app', 'Token cannot be blank.')]
                ]
            ];
        }

        $user = \app\models\User::findByVerificationToken($token);
        if (!$user) {
            Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
            return [
                'errors' => [
                    'token' => [Yii::t('app', 'Invalid or expired verification token.')]
                ]
            ];
        }

        $user->status = \app\models\User::STATUS_ACTIVE;
        $user->verification_token = null;

        if ($user->save()) {
            return [
                'message' => Yii::t('app', 'Your email has been verified successfully. You can now login.')
            ];
        }

        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return [
            'errors' => $user->errors
        ];
    }
}
