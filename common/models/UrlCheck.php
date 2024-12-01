<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class UrlCheck extends ActiveRecord
{
    public static function tableName()
    {
        return 'url_checks';
    }

    public function rules()
    {
        return [
            [['url', 'frequency', 'retry_count', 'retry_delay'], 'required'],
            ['url', 'url'],
            [['frequency', 'retry_count', 'retry_delay'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'url' => 'URL для проверки',
            'frequency' => 'Частота проверки (в минутах)',
            'retry_count' => 'Количество повторов в случае ошибки',
            'retry_delay' => 'Задержка между попытками (в минутах)',
        ];
    }

}
