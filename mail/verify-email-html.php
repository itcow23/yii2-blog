<?php
use yii\helpers\Html;

/** @var \yii\web\View $this */
/** @var \app\models\User $user */
/** @var string $verifyLink */
?>
<h2>Xác thực tài khoản của bạn</h2>
<p>Xin chào <?= Html::encode($user->username) ?>,</p>
<p>Cảm ơn bạn đã đăng ký tại <strong>Yii2 Blog</strong>.</p>
<p>Vui lòng click vào đường dẫn dưới đây để kích hoạt tài khoản của bạn:</p>
<p><?= Html::a(Html::encode($verifyLink), $verifyLink) ?></p>
<p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
