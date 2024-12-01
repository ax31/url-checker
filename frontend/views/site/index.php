<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'URL Checker';

?>

<h1>Добавить URL для проверки</h1>

<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'url')->textInput(['placeholder' => 'Введите URL']) ?>
<?= $form->field($model, 'frequency')->dropDownList([1 => '1 минута', 5 => '5 минут', 10 => '10 минут']) ?>
<?= $form->field($model, 'retry_count')->textInput(['type' => 'number', 'min' => 0]) ?>
<?= $form->field($model, 'retry_delay')->textInput(['type' => 'number', 'min' => 0]) ?>

<div class="form-group">
    <?= Html::submitButton('Добавить', ['class' => 'btn btn-success']) ?>
</div>

<?php ActiveForm::end(); ?>
