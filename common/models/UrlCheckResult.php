<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class UrlCheckResult extends ActiveRecord
{
    public static function tableName()
    {
        return 'url_check_results';
    }

    public function rules()
    {
        return [
            [['url_check_id', 'timestamp', 'http_code', 'attempt'], 'required'],
            [['url_check_id', 'http_code', 'attempt'], 'integer'],
            ['response', 'string'],
            ['timestamp', 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ];
    }
}
