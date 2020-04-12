<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\SpeechQueue $queue
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
if ($queue->motion) {
    $layout->addBreadcrumb($queue->motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($queue->motion));
}
$layout->addBreadcrumb(Yii::t('speech', 'admin_bc'));

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/admin-widget.vue.php');
$layout->addVueTemplate('@app/views/speech/admin-subqueue.vue.php');

$initData = $queue->getAdminApiObject();

$this->title = Yii::t('speech', 'admin_title');
?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="manageSpeechQueue content">
    <div class="sr-only" id="speachListBackendTitle">Redeliste verwalten</div>
    <section aria-labelledby="speachListBackendTitle"
             data-antragsgruen-widget="Backend/CurrentSpeachList"
             data-queue="<?= Html::encode(json_encode($initData)) ?>">
        <div class="content">
            <div class="speechAdmin"></div>
        </div>
    </section>
</div>
