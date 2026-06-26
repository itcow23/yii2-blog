<?php

namespace app\modules\api\controllers;

use app\models\Category;
use app\modules\api\models\forms\CategoryForm;
use app\modules\api\models\search\CategorySearch;
use app\rbac\Permission;
use Yii;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class CategoryController extends BaseController
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
                    'allow' => true,
                    'actions' => ['create', 'update', 'delete'],
                    'roles' => [Permission::MANAGE_CATEGORIES],
                ]
            ],
        ];
        return $behaviors;
    }

    /**
     * Lists all Category models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new CategorySearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $dataProvider;
    }

    /**
     * Displays a single Category model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    /**
     * Creates a new Category model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new CategoryForm();
        $model->load($this->request->post(), '');
        if ($model->save()) {
            return $model;
        }
        Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
        return $model->errors;
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
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
     * Deletes an existing Category model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        try {
            $model->delete();
        } catch (\yii\db\Exception $e) {
            Yii::$app->response->statusCode = self::HTTP_UNPROCESSABLE_ENTITY;
            return ['message' => Yii::t('app', 'Cannot delete this category')];
        }

        return [
            'message' => Yii::t('app', 'Category deleted successfully.'),
        ];
    }

    /**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Category the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CategoryForm::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
