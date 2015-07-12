<?php

namespace app\models\forms;

use app\models\db\ConsultationAgendaItem;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsTag;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\db\MotionSupporter;
use app\models\exceptions\FormError;
use yii\base\Model;

class MotionEditForm extends Model
{
    /** @var ConsultationMotionType */
    public $motionType;

    /** @var ConsultationAgendaItem */
    public $agendaItem;

    /** @var MotionSupporter[] */
    public $supporters = [];

    /** @var array */
    public $tags = [];

    /** @var MotionSection[] */
    public $sections = [];

    /** @var null|int */
    public $motionId = null;

    private $adminMode = false;

    /**
     * @param ConsultationMotionType $motionType
     * @param null|ConsultationAgendaItem
     * @param null|Motion $motion
     */
    public function __construct(ConsultationMotionType $motionType, $agendaItem, $motion)
    {
        parent::__construct();
        $this->motionType = $motionType;
        $this->agendaItem = $agendaItem;
        $motionSections   = [];
        if ($motion) {
            $this->motionId   = $motion->id;
            $this->supporters = $motion->motionSupporters;
            foreach ($motion->tags as $tag) {
                $this->tags[] = $tag->id;
            }
            foreach ($motion->sections as $section) {
                $motionSections[$section->consultationSetting->id] = $section;
            }
        }
        $this->sections = [];
        foreach ($motionType->motionSections as $sectionType) {
            if (isset($motionSections[$sectionType->id])) {
                $this->sections[] = $motionSections[$sectionType->id];
            } else {
                $section            = new MotionSection();
                $section->sectionId = $sectionType->id;
                $section->data      = '';
                $section->refresh();
                $this->sections[] = $section;
            }
        }
    }


    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'type'], 'number'],
            [['supporters', 'tags'], 'safe'],
        ];
    }

    /**
     * @param bool $set
     */
    public function setAdminMode($set)
    {
        $this->adminMode = $set;
    }

    /**
     * @param Motion $motion
     */
    public function cloneSupporters(Motion $motion)
    {
        foreach ($motion->motionSupporters as $supp) {
            $suppNew = new MotionSupporter();
            $suppNew->setAttributes($supp->getAttributes());
            $this->supporters[] = $suppNew;
        }
    }

    /**
     * @param array $data
     * @param bool $safeOnly
     */
    public function setAttributes($data, $safeOnly = true)
    {
        list($values, $files) = $data;
        parent::setAttributes($values, $safeOnly);
        foreach ($this->sections as $section) {
            if (isset($values['sections'][$section->consultationSetting->id])) {
                $section->getSectionType()->setMotionData($values['sections'][$section->consultationSetting->id]);
            }
            if (isset($files['sections']) && isset($files['sections']['tmp_name'])) {
                if (!empty($files['sections']['tmp_name'][$section->consultationSetting->id])) {
                    $data = [];
                    foreach ($files['sections'] as $key => $vals) {
                        if (isset($vals[$section->consultationSetting->id])) {
                            $data[$key] = $vals[$section->consultationSetting->id];
                        }
                    }
                    $section->getSectionType()->setMotionData($data);
                }
            }
        }
    }

    /**
     * @throws FormError
     */
    private function createMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->consultationSetting;
            if ($section->data == '' && $type->required) {
                $errors[] = 'Keine Daten angegeben (Feld: ' . $type->title . ')';
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%max%', $type->maxLen, 'Maximum length of %max% exceeded');
            }
        }

        try {
            $this->motionType->getMotionInitiatorFormClass()->validateMotion();
        } catch (FormError $e) {
            $errors = array_merge($errors, $e->getMessages());
        }

        if (count($errors) > 0) {
            throw new FormError($errors);
        }
    }

    /**
     * @throws FormError
     * @return Motion
     */
    public function createMotion()
    {
        $consultation = $this->motionType->consultation;

        if (!$this->motionType->getMotionPolicy()->checkMotionSubmit()) {
            throw new FormError('Keine Berechtigung zum Anlegen von Anträgen.');
        }

        $motion = new Motion();

        $this->setAttributes([$_POST, $_FILES]);
        $this->supporters = $this->motionType->getMotionInitiatorFormClass()->getMotionSupporters($motion);

        $this->createMotionVerify();

        $motion->status         = Motion::STATUS_DRAFT;
        $motion->consultationId = $this->motionType->consultationId;
        $motion->textFixed      = ($consultation->getSettings()->adminsMayEdit ? 0 : 1);
        $motion->title          = '';
        $motion->titlePrefix    = '';
        $motion->dateCreation   = date('Y-m-d H:i:s');
        $motion->motionTypeId   = $this->motionType->id;
        $motion->cache          = '';
        $motion->agendaItemId   = ($this->agendaItem ? $this->agendaItem->id : null);

        if ($motion->save()) {
            $this->motionType->getMotionInitiatorFormClass()->submitMotion($motion);

            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            foreach ($this->sections as $section) {
                $section->motionId = $motion->id;
                $section->save();
            }

            $motion->refreshTitle();
            $motion->save();

            return $motion;
        } else {
            throw new FormError('Ein Fehler beim Anlegen ist aufgetreten');
        }
    }

    /**
     * @throws FormError
     */
    private function saveMotionVerify()
    {
        $errors = [];

        foreach ($this->sections as $section) {
            $type = $section->consultationSetting;
            if ($section->data == '' && $type->required) {
                $errors[] = 'Keine Daten angegeben (Feld: ' . $type->title . ')';
            }
            if (!$section->checkLength()) {
                $errors[] = str_replace('%max%', $type->maxLen, 'Maximum length of %max% exceeded');
            }
        }

        $this->motionType->getMotionInitiatorFormClass()->validateMotion();

        if (count($errors) > 0) {
            throw new FormError(implode("\n", $errors));
        }
    }


    /**
     * @param Motion $motion
     * @throws FormError
     */
    public function saveMotion(Motion $motion)
    {
        $consultation = $this->motionType->consultation;
        if (!$this->motionType->getMotionPolicy()->checkMotionSubmit()) {
            throw new FormError('Keine Berechtigung zum Anlegen von Anträgen.');
        }

        $this->supporters = $this->motionType->getMotionInitiatorFormClass()->getMotionSupporters($motion);

        if (!$this->adminMode) {
            $this->saveMotionVerify();
        }

        if ($motion->save()) {
            $this->motionType->getMotionInitiatorFormClass()->submitMotion($motion);

            // Tags
            foreach ($motion->tags as $tag) {
                $motion->unlink('tags', $tag, true);
            }
            foreach ($this->tags as $tagId) {
                /** @var ConsultationSettingsTag $tag */
                $tag = ConsultationSettingsTag::findOne(['id' => $tagId, 'consultationId' => $consultation->id]);
                if ($tag) {
                    $motion->link('tags', $tag);
                }
            }

            // Sections
            foreach ($motion->sections as $section) {
                $section->delete();
            }
            foreach ($this->sections as $section) {
                $section->motionId = $motion->id;
                $section->save();
            }

            $motion->refreshTitle();
            $motion->save();
        } else {
            throw new FormError('Ein Fehler beim Anlegen ist aufgetreten');
        }
    }
}
