<?php

namespace ylab\administer;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\i18n\PhpMessageSource;
use Yii;

/**
 * @inheritdoc
 */
class Module extends \yii\base\Module
{
    /**
     * @var array
     *
     * Example:
     * ```
     * [
     *     Post::class,
     *     [
     *         'class' => Tag::class,
     *         'url' => 'post-tags',
     *         'labels' => ['Теги', 'Тег', 'Тега'],
     *         'menuIcon' => 'dashboard',
     *     ],
     * ]
     * ```
     */
    public $modelsConfig = [];
    /**
     * Url prefix for module actions.
     *
     * @var string
     */
    public $urlPrefix = 'admin';
    /**
     * Base url for uploads.
     *
     * @var string
     */
    public $uploadsUrl = '/uploads/';
    /**
     * Base path for uploads.
     *
     * @var string
     */
    public $uploadsPath = '@webroot' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    /**
     * Class that implements the [[UserDataInterface]].
     *
     * @var string|array
     */
    public $userDataClass;

    private $userData;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->registerTranslations();
        Yii::$app->user->loginUrl = [$this->id . '/user/login'];
        $urlManager = \Yii::$app->getUrlManager();
        $rules = [];
        if ($this->getUserData() !== null) {
            $rules["$this->urlPrefix/logout"] = "$this->id/user/logout";
            if ($this->getUserData()->getLoginForm() !== null) {
                $rules["$this->urlPrefix/login"] = "$this->id/user/login";
            }
        }
        $rules = ArrayHelper::merge($rules, [
            "$this->urlPrefix" => "$this->id/crud/default",
            "$this->urlPrefix/<modelClass:[\\w-]+>" => "$this->id/crud/index",
            "$this->urlPrefix/<modelClass:[\\w-]+>/<action:(autocomplete)>/<id:\\d+>" => "$this->id/api/<action>",
            "$this->urlPrefix/<modelClass:[\\w-]+>/<action:[\\w-]+>" => "$this->id/crud/<action>",
            "$this->urlPrefix/<modelClass:[\\w-]+>/<action:[\\w-]+>/<id:\\d+>" => "$this->id/crud/<action>",
        ]);
        $urlManager->addRules($rules);
        $this->normalizeModelsConfig();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        if ($this->getBehavior('access') !== null) {
            return parent::behaviors();
        }

        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'controllers' => ['admin/user'],
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'controllers' => ['admin/api', 'admin/crud'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ]
        ]);
    }

    /**
     * Get menu items for `Menu` widget.
     *
     * @return array
     */
    public function getMenuItems()
    {
        $items = [];
        foreach ($this->modelsConfig as $config) {
            $items[] = [
                'label' => $config['labels'][0],
                'icon' => $config['menuIcon'],
                'url' => ['index', 'modelClass' => $config['url']],
            ];
        }
        return $items;
    }

    /**
     * Get the implementation of the UserDataInterface.
     *
     * @return UserDataInterface|null
     */
    public function getUserData()
    {
        if (is_null($this->userData) && !is_null($this->userDataClass)) {
            $this->userData = Yii::createObject($this->userDataClass);
        }

        return $this->userData;
    }

    /**
     * Make config valid, set unspecified fields to default values.
     *
     * @throws InvalidConfigException
     */
    protected function normalizeModelsConfig()
    {
        $config = [];
        foreach ($this->modelsConfig as $modelConfig) {
            if (is_string($modelConfig)) {
                $modelConfig = ['class' => $modelConfig];
            } elseif (!isset($modelConfig['class'])) {
                throw new InvalidConfigException('Each modelConfig item must contain "class" field.');
            }
            $url = isset($modelConfig['url'])
                ? $modelConfig['url']
                : Inflector::camel2id(StringHelper::basename($modelConfig['class']));
            $labels = [];
            foreach ([true, false, false] as $pos => $plurals) {
                $labels[$pos] = isset($modelConfig['labels'][$pos])
                    ? $modelConfig['labels'][$pos]
                    : ($plurals
                        ? Inflector::pluralize(StringHelper::basename($modelConfig['class']))
                        : StringHelper::basename($modelConfig['class'])
                    );
            }
            $config[$url] = [
                'class' => $modelConfig['class'],
                'labels' => $labels,
                'menuIcon' => isset($modelConfig['menuIcon']) ? $modelConfig['menuIcon'] : 'dashboard',
                'url' => $url,
            ];
        }
        $this->modelsConfig = $config;
    }

    /**
     * Register needed i18n files
     */
    protected function registerTranslations()
    {
        if (!isset(\Yii::$app->i18n->translations['ylab/administer'])
            && !isset(\Yii::$app->i18n->translations['ylab/administer/*'])
        ) {
            \Yii::$app->i18n->translations['ylab/administer'] = [
                'class' => PhpMessageSource::class,
                'basePath' => '@ylab/administer/messages',
                'forceTranslation' => true,
                'fileMap' => [
                    'ylab/administer' => 'administer.php',
                ],
            ];
        }
    }
}
