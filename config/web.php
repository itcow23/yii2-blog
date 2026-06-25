<?php

use yii\web\Response;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$cacheDriver = $_ENV['CACHE_DRIVER'] ?? 'file';
$cacheConfig = [
    'class' => \yii\caching\FileCache::class,
];

if ($cacheDriver === 'redis') {
    $cacheConfig = [
        'class' => \yii\redis\Cache::class,
        'redis' => 'redis',
    ];
}

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],

    'on beforeRequest' => function () {
        $lang = Yii::$app->request->get('lang');
        if (!$lang) {
            $lang = Yii::$app->request->headers->get('Accept-Language');
        }
        if ($lang) {
            $lang = preg_split('/[;,]/', $lang)[0];
            Yii::$app->language = trim($lang);
        }
    },

    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => ($_ENV['MAIL_USE_FILE_TRANSPORT'] ?? 'true') === 'true',
                'viewPath' => '@app/mail',
                'transport' => [
                    'scheme' => ($_ENV['SMTP_ENCRYPTION'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp',
                    'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
                    'username' => $_ENV['SMTP_USERNAME'] ?? '',
                    'password' => $_ENV['SMTP_PASSWORD'] ?? '',
                    'port' => $_ENV['SMTP_PORT'] ?? 587,
                ]
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => $_ENV['COOKIE_VALIDATION_KEY'],
            'parsers' => [
                'application/json' => 'yii\web\JsonParser'
            ]
        ],
        'cache' => $cacheConfig,
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // auth
                'POST api/auth/register' => 'api/auth/register',
                'POST api/auth/login' => 'api/auth/login',
                'POST api/auth/logout' => 'api/auth/logout',
                'GET api/auth/me' => 'api/auth/me',
                'GET api/auth/verify-email' => 'api/auth/verify-email',

                // admin
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        'api/categories' => 'api/category',
                        'api/tags'       => 'api/tag',
                        'api/posts'      => 'api/post',
                    ],
                    'pluralize' => false,
                ],

                // comments
                'POST api/posts/<post_id:\d+>/comments' => 'api/comment/create',
                'PUT api/comments/<id:\d+>'             => 'api/comment/update',
                'POST api/comments/<id:\d+>/hide'        => 'api/comment/hide',
                'DELETE api/comments/<id:\d+>'          => 'api/comment/delete',

                //like
                'POST api/posts/<post_id:\d+>/like'      => 'api/post/like',

                //upload
                'POST api/media/upload' => 'api/media/upload',

                // AI assistant
                'POST api/ai/generate-title'   => 'api/ai/generate-title',
                'POST api/ai/generate-summary' => 'api/ai/generate-summary',
                'POST api/ai/improve-text'     => 'api/ai/improve-text',

            ],
        ],
        'response' => [
            'class' => Response::class,
            'format' => Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                $route = Yii::$app->requestedRoute;

                if ($route !== null && str_starts_with($route, 'api/')) {
                    $isSuccess = $response->isSuccessful;
                    $data = $response->data;
                    $meta = null;

                    if ($isSuccess && is_array($data)) {
                        if (isset($data['items']) && is_array($data['items'])) {
                            $meta = $data['_meta'] ?? null;
                            $data = $data['items'];
                        }
                    }

                    $message = $isSuccess ? 'Success' : ($response->statusText ?: 'Error');

                    if (is_array($data) && isset($data['message'])) {
                        $message = $data['message'];
                        if ($isSuccess) {
                            if (count($data) === 1) {
                                $data = null;
                            } else {
                                unset($data['message']);
                                if (count($data) === 1) {
                                    $data = reset($data);
                                }
                            }
                        }
                    }

                    $formatData = [
                        'status' => $isSuccess ? 'success' : 'error',
                        'code' => $response->statusCode,
                        'message' => $message,
                        'data' => !$isSuccess && isset($data['errors']) ? $data['errors'] : $data,
                    ];

                    if ($meta) {
                        $formatData['pagination'] = [
                            'total' => isset($meta['totalCount']) ? (int)$meta['totalCount'] : null,
                            'page' => isset($meta['currentPage']) ? (int)$meta['currentPage'] : null,
                            'limit' => isset($meta['perPage']) ? (int)$meta['perPage'] : null,
                            'total_page' => isset($meta['pageCount']) ? (int)$meta['pageCount'] : null,
                        ];
                    }

                    $response->data = $formatData;
                }
            }
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'cache' => 'cache',
        ],
        'r2Component' => [
            'class' => \app\components\R2Component::class,
            'bucket' => $_ENV['R2_BUCKET_NAME'],
            'publicUrl' => $_ENV['R2_PUBLIC_URL'],
            'accessKey' => $_ENV['R2_ACCESS_KEY_ID'],
            'secretKey' => $_ENV['R2_SECRET_ACCESS_KEY'],
            'endPoint' => $_ENV['R2_ENDPOINT'],
        ],
        'aiWorkerComponent' => [
            'class' => \app\components\AiWorkerComponent::class,
            'accountId' => $_ENV['CF_ACCOUNT_ID'],
            'apiToken' => $_ENV['CF_API_TOKEN'],
            'model' => $_ENV['CF_AI_MODEL'],
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'hostname' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'database' => $_ENV['REDIS_DATABASE'] ?? 0,
            'password' => !empty($_ENV['REDIS_PASSWORD']) ? $_ENV['REDIS_PASSWORD'] : null,
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
            ],
        ],


    ],
    'params' => $params,
    'modules' => [
        'api' => [
            'class' => \app\modules\api\Module::class,
        ]
    ]
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
