<?php
use yii\helpers\Html;
/* @var $this      yii\web\View */
/* @var $exception Exception */
$this->title = 'Error';
?>
<div class="card mt-4">
    <div class="card-body text-center py-5">
        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
        <h4><?= Html::encode($exception->getMessage()) ?></h4>
        <p class="text-muted">An unexpected error occurred. Please try again later.</p>
        <?= Html::a('Go to dashboard', ['/site/index'], ['class' => 'btn btn-primary']) ?>
    </div>
</div>
