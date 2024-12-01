<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Панель управления';
?>

<h1><?= Html::encode($this->title) ?></h1>

<h2>Список URL-ов</h2>

<?= GridView::widget([
    'dataProvider' => $urlDataProvider,
    'columns' => [
        'id',
        'url',
        'frequency',
        'retry_count',
        'retry_delay',
    ],
]) ?>

<h2>Список проверок</h2>

<?= GridView::widget([
    'dataProvider' => $checkResultsDataProvider,
    'columns' => [
        [
            'attribute' => 'url_check_id',
            'label' => 'URL ID',
        ],
        [
            'attribute' => 'timestamp',
            'label' => 'Время проверки',
        ],
        [
            'attribute' => 'http_code',
            'label' => 'HTTP-код',
        ],
        [
            'attribute' => 'response',
            'label' => 'Ответ',
            'value' => function ($model) {
                return Html::tag(
                    'span',
                    Html::encode(mb_strimwidth($model->response, 0, 200, '...')),
                    ['title' => Html::encode($model->response)]
                );
            },
            'format' => 'raw', // Чтобы HTML-теги отображались корректно
        ],
        [
            'attribute' => 'attempt',
            'label' => 'Попытка',
        ],
    ],
]) ?>
