<?php
define('YII_ENV', 'test');
define('YII_DEBUG', true);

require_once __DIR__ . '/../vendor/autoload.php';

// Nạp các biến cấu hình từ file .env local của bạn vào môi trường test
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
