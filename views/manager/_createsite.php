<?php

use yii\helpers\Html;

/**
 * @var string[] $errors
 * @var string $mode
 * @var \app\models\forms\SiteCreateForm $model
 */

$t = function ($string) {
    return \Yii::t('wizard', $string);
};


?>
<div id="SiteCreateWizard" class="wizard" data-mode="<?= Html::encode($mode) ?>">
    <ul class="steps">
        <li data-target="#stepPurpose" class="stepPurpose">
            <?= $t('step_purpose') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepMotions" class="stepMotions">
            <?= $t('step_motions') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepAmendments" class="stepAmendments">
            <?= $t('step_amendments') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepSpecial" class="stepSpecial">
            <?= $t('step_special') ?><span class="chevron"></span>
        </li>
        <li data-target="#stepSite" class="stepSite">
            <?= $t('step_site') ?><span class="chevron"></span>
        </li>
    </ul>
</div>
<div class="content">
    <?= $this->render('_createsite_purpose', ['model' => $model, 'errors' => $errors, 't' => $t]) ?>
    <?= $this->render('_createsite_single_motion', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_motion_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_motion_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_motion_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_supporters', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_amendments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_amend_single_para', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_amend_who', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_amend_deadline', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_amend_screening', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_comments', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_agenda', ['model' => $model, 't' => $t]) ?>
    <?= $this->render('_createsite_opennow', ['model' => $model, 't' => $t]) ?>
    <?php
    switch ($mode) {
        case 'subdomain':
            echo $this->render('_createsite_sitedata_subdomain', ['model' => $model, 't' => $t]);
            break;
        case 'singlesite':
            echo $this->render('_createsite_sitedata_singlesite', ['model' => $model, 't' => $t]);
            break;
    }
    ?>
</div>
