<?php

namespace app\modules\api\controllers;

use app\models\Tag;
use app\modules\api\models\forms\TagForm;
use app\modules\api\models\search\TagSearch;
use app\rbac\Permission;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

/**
 * TagController implements the CRUD actions for Tag model.
 */
class TagController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => ['GET', 'HEAD'],
                'view' => ['GET', 'HEAD'],
                'create' => ['POST'],
                'update' => ['PUT', 'PATCH'],
                'delete' => ['DELETE'],
            ],
        ];
        $behaviors['authenticator']['optional'] = ['index', 'view'];
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['index', 'view'],
                ],
                [
                    'allow'   => true,
                    'actions' => ['create', 'update', 'delete'],
                    'roles'   => [Permission::MANAGE_TAGS],
                ],
            ],
        ];
        return $behaviors;
    }

    /**
     * Lists all Tag models.
     * GET /api/tags
     */
    public function actionIndex()
    {
        $searchModel = new TagSearch();
        return $searchModel->search($this->request->queryParams);
    }

    /**
     * Displays a single Tag model.
     * GET /api/tags/<id>
     *
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Creates a new Tag model.
     * POST /api/tags
     */
    public function actionCreate()
    {
        $model = new TagForm();
        $model->load($this->request->post(), '');

        if ($model->save()) {
            Yii::$app->response->statusCode = self::HTTP_CREATED;
            return $model;
        }

        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return $model->errors;
    }

    /**
     * Updates an existing Tag model.
     * PUT /api/tags/<id>
     *
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->load($this->request->post(), '');

        if ($model->save()) {
            return $model;
        }

        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return $model->errors;
    }

    /**
     * Deletes an existing Tag model.
     * DELETE /api/tags/<id>
     *
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            $model->delete();
        } catch (\yii\db\Exception $e) {
            Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
            return ['message' => Yii::t('app', 'Cannot delete this tag')];
        }

        return ['message' => Yii::t('app', 'Tag deleted successfully.')];
    }

    /**
     * Finds the Tag model based on its primary key value.
     *
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = TagForm::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
