<?php

namespace ylab\administer\grid;

use yii\db\ActiveRecord;

/**
 * Basic class for filters in GridView.
 */
abstract class BaseFilterInput
{
    /**
     * @var ActiveRecord
     */
    private $model;
    /**
     * @var string
     */
    private $attribute;

    /**
     * @param $model
     * @param $attribute
     */
    public function __construct(ActiveRecord $model, $attribute)
    {
        $this->model = $model;
        $this->attribute = $attribute;
    }

    /**
     * Returns filter as string or configuration for creation by the GridView widget.
     *
     * @param array $options
     * @return string|array|boolean|null
     */
    abstract public function render(array $options = []);
}