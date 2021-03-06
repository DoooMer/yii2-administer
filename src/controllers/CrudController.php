<?php

namespace ylab\administer\controllers;

use yii\base\InvalidConfigException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use ylab\administer\components\ParamBindingTrait;
use ylab\administer\helpers\ModelHelper;
use ylab\administer\Module;

/**
 * Controller for all CRUD actions.
 *
 * {@inheritdoc}
 * @property Module $module
 */
class CrudController extends Controller
{
    use ParamBindingTrait;

    /**
     * @inheritdoc
     */
    public $layout = 'layout';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $field = \Yii::$app->getRequest()->getQueryParam('field', 'default');
        $url = \Yii::$app->getRequest()->getQueryParam('modelClass', 'default');
        return [
            'image-upload' => [
                'class' => 'vova07\imperavi\actions\UploadFileAction',
                'url' => $this->module->uploadsUrl . $url . '/' . $field,
                'path' => $this->module->uploadsPath . $url . DIRECTORY_SEPARATOR . $field,
            ],
            'images-get' => [
                'class' => 'vova07\imperavi\actions\GetImagesAction',
                'url' => $this->module->uploadsUrl . $url . '/' . $field,
                'path' => $this->module->uploadsPath . $url . DIRECTORY_SEPARATOR . $field,
            ],
            'image-delete' => [
                'class' => 'vova07\imperavi\actions\DeleteFileAction',
                'url' => $this->module->uploadsUrl . $url . '/' . $field,
                'path' => $this->module->uploadsPath . $url . DIRECTORY_SEPARATOR . $field,
            ],
        ];
    }

    /**
     * Show default module page.
     *
     * @return string
     */
    public function actionDefault()
    {
        return $this->render('default');
    }

    /**
     * Lists all models.
     *
     * @param string $modelClass
     * @return string
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionIndex($modelClass)
    {
        $this->module->getAccessControl()->checkAccess($this->action->id, $this->modelConfig['url']);

        $model = ModelHelper::createModel($modelClass);
        return $this->render('index', [
            'gridView' => $model->renderGrid(\Yii::$app->getRequest()->getQueryParams(), $this->modelConfig['url']),
            'title' => $this->modelConfig['labels'][0],
            'breadcrumbs' => $model->getBreadcrumbs('index', null, $this->modelConfig['labels'][0]),
            'buttons' => $model->getButtons('index', $this->modelConfig['url']),
        ]);
    }

    /**
     * Displays a single model.
     *
     * @param string $modelClass
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionView($modelClass, $id)
    {
        $this->module->getAccessControl()->checkAccess($this->action->id, $this->modelConfig['url']);

        $model = ModelHelper::findModel($modelClass, $id);
        return $this->render('view', [
            'detailView' => $model->renderDetailView(),
            'title' => \Yii::t('ylab/administer', 'View') . " {$this->modelConfig['labels'][2]} #$id",
            'breadcrumbs' => $model->getBreadcrumbs(
                'view',
                $this->modelConfig['url'],
                $this->modelConfig['labels'][0],
                $id
            ),
            'buttons' => $model->getButtons('view', $this->modelConfig['url'], $id),
        ]);
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $modelClass
     * @return string|Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionCreate($modelClass)
    {
        $this->module->getAccessControl()->checkAccess($this->action->id, $this->modelConfig['url']);

        $model = ModelHelper::createModel($modelClass);
        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return $this->insertUpdateRedirect($model);
        }

        return $this->render('create', [
            'form' => $model->renderForm($this->modelConfig['url']),
            'title' => \Yii::t('ylab/administer', 'Create') . " {$this->modelConfig['labels'][1]}",
            'breadcrumbs' => $model->getBreadcrumbs(
                'create',
                $this->modelConfig['url'],
                $this->modelConfig['labels'][0]
            ),
            'buttons' => $model->getButtons('create', $this->modelConfig['url']),
        ]);
    }

    /**
     * Updates an existing model.
     * If update is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $modelClass
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionUpdate($modelClass, $id)
    {
        $this->module->getAccessControl()->checkAccess($this->action->id, $this->modelConfig['url']);

        $model = ModelHelper::findModel($modelClass, $id);
        if ($model->load(\Yii::$app->request->post()) && $model->save()) {
            return $this->insertUpdateRedirect($model);
        }
        return $this->render('update', [
            'form' => $model->renderForm($this->modelConfig['url']),
            'title' => \Yii::t('ylab/administer', 'Update') . " {$this->modelConfig['labels'][1]} #$id",
            'breadcrumbs' => $model->getBreadcrumbs(
                'update',
                $this->modelConfig['url'],
                $this->modelConfig['labels'][0],
                $id
            ),
            'buttons' => $model->getButtons('update', $this->modelConfig['url'], $id),
        ]);
    }

    /**
     * Deletes an existing model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param string $modelClass
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($modelClass, $id)
    {
        $this->module->getAccessControl()->checkAccess($this->action->id, $this->modelConfig['url']);

        ModelHelper::findModel($modelClass, $id)->delete();
        return $this->redirect(['index', 'modelClass' => $this->modelConfig['url']]);
    }

    /**
     * Redirection depending on the button "Apply" or "Save".
     *
     * @param $model
     * @return Response
     */
    private function insertUpdateRedirect($model)
    {
        if (\Yii::$app->request->post('apply') == 1) {
            return $this->redirect([
                'update',
                'modelClass' => $this->modelConfig['url'],
                'id' => $model->id,
            ]);
        }
        return $this->redirect([
            'index',
            'modelClass' => $this->modelConfig['url'],
        ]);
    }
}
