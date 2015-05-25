<?php

namespace app\models\initiatorForms;

use app\models\db\ConsultationMotionType;

class WithSupporters extends DefaultFormBase
{
    /**
     * @return string
     */
    public static function getTitle()
    {
        return 'Mit UnterstützerInnen';
    }

    /** @var int */
    protected $minSupporters = 1;

    /** @var bool */
    protected $suppHaveOrganizations = false;

    /**
     * @param ConsultationMotionType $motionType
     * @param string $settings
     */
    public function __construct(ConsultationMotionType $motionType, $settings)
    {
        parent::__construct($motionType);
        $json = [];
        try {
            if ($settings != '') {
                $json = json_decode($settings, true);
            }
        } catch (\Exception $e) {
        }

        if (isset($json['minSupporters'])) {
            $this->minSupporters = IntVal($json['minSupporters']);
        }
        if (isset($json['supportersHaveOrganizations'])) {
            $this->suppHaveOrganizations = ($json['supportersHaveOrganizations'] == true);
        }
    }

    /**
     * @return string|null
     */
    public function getSettings()
    {
        return json_encode([
            'minSupporters'               => $this->minSupporters,
            'supportersHaveOrganizations' => $this->suppHaveOrganizations
        ]);
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        if (isset($settings['minSupporters']) && $settings['minSupporters'] >= 0) {
            $this->minSupporters = IntVal($settings['minSupporters']);
        }
        $this->suppHaveOrganizations = (isset($settings['supportersHaveOrganizations']));
    }

    /**
     * @return bool
     */
    public static function hasSupporters()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getMinNumberOfSupporters()
    {
        return $this->minSupporters;
    }

    /**
     * @param int $num
     */
    public function setMinNumberOfSupporters($num)
    {
        $this->minSupporters = $num;
    }

    /**
     * @return bool
     */
    public function supportersHaveOrganizations()
    {
        return $this->suppHaveOrganizations;
    }


    /**
     * @return bool
     */
    public function hasFullTextSupporterField()
    {
        return true;
    }
}
