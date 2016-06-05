<?php

namespace app\models\forms;

use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationText;
use app\models\db\Site;
use app\models\db\User;
use app\models\exceptions\FormError;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\ISupportType;
use yii\base\Model;
use yii\helpers\Html;

class SiteCreateForm extends Model
{

    /** @var string */
    public $contact;
    public $title;
    public $subdomain;
    public $organization;

    const WORDING_MOTIONS   = 1;
    const WORDING_MANIFESTO = 2;
    public $wording = 1;

    /** @var bool */
    public $singleMotion    = false;
    public $hasAmendments   = true;
    public $amendSinglePara = false;
    public $motionScreening = true;
    public $amendScreening  = true;

    /** @var int */
    public $motionsInitiatedBy    = 2;
    public $amendmentsInitiatedBy = 2;
    const MOTION_INITIATED_ADMINS    = 1;
    const MOTION_INITIATED_LOGGED_IN = 2;
    const MOTION_INITIATED_ALL       = 3;

    /** @var null|\DateTime */
    public $motionDeadline = null;
    /** @var null|\DateTime */
    public $amendmentDeadline = null;

    public $needsSupporters = false;
    public $minSupporters   = 3;

    /** @var bool */
    public $hasComments = false;
    public $hasAgenda   = false;

    public $openNow = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'contact', 'organization', 'subdomain'], 'required'],
            [
                'subdomain',
                'unique',
                'targetClass' => Site::class,
            ],
            [['contact', 'title', 'subdomain', 'organization'], 'safe'],
        ];
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);

        $this->wording               = IntVal($values['wording']);
        $this->singleMotion          = ($values['singleMotion'] == 1);
        $this->hasAmendments         = ($values['hasAmendments'] == 1);
        $this->amendSinglePara       = ($values['amendSinglePara'] == 1);
        $this->motionScreening       = ($values['motionScreening'] == 1);
        $this->amendScreening        = ($values['amendScreening'] == 1);
        $this->motionsInitiatedBy    = IntVal($values['motionsInitiatedBy']);
        $this->amendmentsInitiatedBy = IntVal($values['amendInitiatedBy']);
        if ($values['motionsDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['motionsDeadline']);
            if ($deadline) {
                $this->motionDeadline = new \DateTime($deadline);
            }
        }
        if ($values['amendDeadlineExists']) {
            $deadline = Tools::dateBootstraptime2sql($values['amendDeadline']);
            if ($deadline) {
                $this->amendmentDeadline = new \DateTime($deadline);
            }
        }
        $this->needsSupporters = ($values['needsSupporters'] == 1);
        $this->minSupporters   = IntVal($values['minSupporters']);
        $this->hasComments     = ($values['hasComments'] == 1);
        $this->hasAgenda       = ($values['hasAgenda'] == 1);
        $this->openNow         = ($values['openNow'] == 1);
    }

    /**
     * @param User $currentUser
     * @return Site
     * @throws FormError
     */
    public function createSite(User $currentUser)
    {
        if (!Site::isSubdomainAvailable($this->subdomain)) {
            throw new FormError(\Yii::t('manager', 'site_err_subdomain'));
        }
        if (!$this->validate()) {
            throw new FormError($this->getErrors());
        }

        $site               = new Site();
        $site->title        = $this->title;
        $site->titleShort   = $this->title;
        $site->organization = $this->organization;
        $site->contact      = $this->contact;
        $site->subdomain    = $this->subdomain;
        $site->public       = 1;
        $site->status       = ($this->openNow ? Site::STATUS_ACTIVE : Site::STATUS_INACTIVE);
        $site->dateCreation = date('Y-m-d H:i:s');
        if (!$site->save()) {
            throw new FormError($site->getErrors());
        }

        $con                     = new Consultation();
        $con->siteId             = $site->id;
        $con->title              = $this->title;
        $con->titleShort         = $this->title;
        $con->urlPath            = $this->subdomain;
        $con->adminEmail         = $currentUser->email;
        $con->amendmentNumbering = 0;
        $con->dateCreation       = date('Y-m-d H:i:s');
        $con->wordingBase        = ($this->wording == static::WORDING_MANIFESTO ? 'de-programm' : 'de-parteitag');

        $settings                   = $con->getSettings();
        $settings->maintainanceMode = !$this->openNow;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $settings->screeningMotions = false;
        } else {
            $settings->screeningMotions = $this->motionScreening;
        }
        if ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $settings->screeningAmendments = false;
        } else {
            $settings->screeningAmendments = $this->amendScreening;
        }
        if ($this->hasAgenda) {
            $settings->startLayoutType = \app\models\settings\Consultation::START_LAYOUT_AGENDA_LONG;
        } else {
            $settings->startLayoutType = \app\models\settings\Consultation::START_LAYOUT_STD;
        }
        $settings->screeningComments = false;
        $con->setSettings($settings);
        if (!$con->save()) {
            $site->delete();
            throw new FormError($con->getErrors());
        }

        $site->link('currentConsultation', $con);
        $site->link('admins', $currentUser);

        if ($this->wording == static::WORDING_MANIFESTO) {
            $type = $this->doCreateManifestoType($con);
            $this->doCreateManifestoSections($type);
        } else {
            $type = $this->doCreateMotionType($con);
            $this->doCreateMotionSections($type);
        }

        if ($this->hasAgenda) {
            $this->createAgenda($con);
        }

        $this->createImprint($site, $con);

        return $site;
    }


    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws FormError
     */
    private function doCreateManifestoType(Consultation $consultation)
    {
        $type                 = new ConsultationMotionType();
        $type->consultationId = $consultation->id;
        $type->titleSingular  = \Yii::t('structure', 'preset_manifesto_singular');
        $type->titlePlural    = \Yii::t('structure', 'preset_manifesto_plural');
        $type->createTitle    = \Yii::t('structure', 'preset_manifesto_call');
        $type->position       = 0;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyMotions = IPolicy::POLICY_ADMINS;
        } elseif ($this->motionsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyMotions = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyMotions = IPolicy::POLICY_NOBODY;
        }
        if (!$this->hasAmendments) {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyAmendments = IPolicy::POLICY_ADMINS;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyAmendments = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        }
        if ($this->hasComments) {
            if (in_array($type->policyAmendments, [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
                $type->policyComments = $type->policyAmendments;
            } else {
                $type->policyComments = IPolicy::POLICY_ALL;
            }
        } else {
            $type->policyComments = IPolicy::POLICY_NOBODY;
        }
        $type->policySupportMotions        = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments     = IPolicy::POLICY_NOBODY;
        $type->contactPhone                = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail                = ConsultationMotionType::CONTACT_REQUIRED;
        $type->supportType                 = ISupportType::ONLY_INITIATOR;
        $type->texTemplateId               = 1;
        $type->amendmentMultipleParagraphs = 1;
        $type->motionLikesDislikes         = 0;
        $type->amendmentLikesDislikes      = 0;
        $type->status                      = ConsultationMotionType::STATUS_VISIBLE;
        $type->layoutTwoCols               = 0;
        $type->deadlineMotions             = ($this->motionDeadline ? $this->motionDeadline->format('Y-m-d H:i:s') : null);
        $type->deadlineAmendments          = ($this->amendmentDeadline ? $this->amendmentDeadline->format('Y-m-d H:i:s') : null);

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    private function doCreateManifestoSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();
    }

    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws FormError
     */
    private function doCreateMotionType(Consultation $consultation)
    {
        $type                 = new ConsultationMotionType();
        $type->consultationId = $consultation->id;
        $type->titleSingular  = \Yii::t('structure', 'preset_motion_singular');
        $type->titlePlural    = \Yii::t('structure', 'preset_motion_plural');
        $type->createTitle    = \Yii::t('structure', 'preset_motion_call');
        $type->position       = 0;
        if ($this->motionsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyMotions = IPolicy::POLICY_ADMINS;
        } elseif ($this->motionsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyMotions = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyMotions = IPolicy::POLICY_NOBODY;
        }
        if (!$this->hasAmendments) {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_ADMINS) {
            $type->policyAmendments = IPolicy::POLICY_ADMINS;
        } elseif ($this->amendmentsInitiatedBy == static::MOTION_INITIATED_LOGGED_IN) {
            $type->policyAmendments = IPolicy::POLICY_LOGGED_IN;
        } else {
            $type->policyAmendments = IPolicy::POLICY_NOBODY;
        }
        if ($this->hasComments) {
            if (in_array($type->policyAmendments, [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
                $type->policyComments = $type->policyAmendments;
            } else {
                $type->policyComments = IPolicy::POLICY_ALL;
            }
        } else {
            $type->policyComments = IPolicy::POLICY_NOBODY;
        }
        $type->policySupportMotions    = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments = IPolicy::POLICY_NOBODY;
        $type->contactPhone            = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail            = ConsultationMotionType::CONTACT_REQUIRED;
        if ($this->needsSupporters) {
            $type->supportType         = ISupportType::GIVEN_BY_INITIATOR;
            $type->supportTypeSettings = json_encode([
                'minSupporters'               => $this->minSupporters,
                'supportersHaveOrganizations' => false,
            ]);
        } else {
            $type->supportType = ISupportType::ONLY_INITIATOR;
        }
        $type->texTemplateId               = 1;
        $type->amendmentMultipleParagraphs = ($this->amendSinglePara ? 0 : 1);
        $type->motionLikesDislikes         = 0;
        $type->amendmentLikesDislikes      = 0;
        $type->status                      = ConsultationMotionType::STATUS_VISIBLE;
        $type->layoutTwoCols               = 0;
        $type->deadlineMotions             = ($this->motionDeadline ? $this->motionDeadline->format('Y-m-d H:i:s') : null);
        $type->deadlineAmendments          = ($this->amendmentDeadline ? $this->amendmentDeadline->format('Y-m-d H:i:s') : null);

        if (!$type->save()) {
            throw new FormError($type->getErrors());
        }

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    private function doCreateMotionSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 2;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_reason');
        $section->required      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $section->save();
    }

    /**
     * @param Consultation $consultation
     */
    private function createAgenda(Consultation $consultation)
    {
        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 0;
        $item->code           = '0.';
        $item->title          = \Yii::t('structure', 'preset_party_top');
        $item->save();

        $wahlItem                 = new ConsultationAgendaItem();
        $wahlItem->consultationId = $consultation->id;
        $wahlItem->parentItemId   = null;
        $wahlItem->position       = 1;
        $wahlItem->code           = '#';
        $wahlItem->title          = \Yii::t('structure', 'preset_party_elections');
        $wahlItem->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 0;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_1leader');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 1;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_2leader');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = $wahlItem->id;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_treasure');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 2;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_motions');
        $item->motionTypeId   = null;
        $item->save();

        $item                 = new ConsultationAgendaItem();
        $item->consultationId = $consultation->id;
        $item->parentItemId   = null;
        $item->position       = 3;
        $item->code           = '#';
        $item->title          = \Yii::t('structure', 'preset_party_misc');
        $item->save();
    }

    /**
     * @var Site $site
     * @param Consultation $consultation
     * @throws FormError
     */
    private function createImprint(Site $site, Consultation $consultation)
    {
        $contactHtml               = nl2br(Html::encode($site->contact));
        $legalText                 = new ConsultationText();
        $legalText->consultationId = $consultation->id;
        $legalText->category       = 'pagedata';
        $legalText->textId         = 'legal';
        $legalText->text           = str_replace('%CONTACT%', $contactHtml, \Yii::t('base', 'legal_template'));
        if (!$legalText->save()) {
            throw new FormError($legalText->getErrors());
        }
    }
}
