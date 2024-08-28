<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Загрузить отчет';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/site-upload.css');
?>

<div class="site-upload">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Перетащите HTML файл с отчетом в область ниже или нажмите для выбора файла:</p>

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data']
    ]); ?>

    <div class="file-upload-container">
        <?= $form->field($model, 'file')->fileInput(['id' => 'fileInput'])->label(false) ?>
        <label for="fileInput" class="file-upload-label">Выберите файл или перетащите сюда</label>
        <div id="fileName" class="file-name">Файл не выбран</div>
    </div>

    <?= Html::submitButton('Загрузить', ['class' => 'btn btn-primary']) ?>

    <?php ActiveForm::end(); ?>
</div>

<script>
    document.getElementById('fileInput').addEventListener('change', function() {
        var fileName = document.getElementById('fileName');
        if (this.files.length > 0) {
            fileName.textContent = this.files[0].name;
        } else {
            fileName.textContent = 'Файл не выбран';
        }
    });
</script>
