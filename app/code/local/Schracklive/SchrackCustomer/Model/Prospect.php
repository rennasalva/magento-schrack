<?php

class Schracklive_SchrackCustomer_Model_Prospect extends Schracklive_SchrackCustomer_Model_Customer {
    const PROSPECT_TYPE_LIGHT = 'light-prospect';
    const PROSPECT_TYPE_FULL  = 'full-prospect';


    function _construct() {
        parent::_construct();
        // TODO: Proxpect / Prospect Light:
        //if() {
        //$this->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAnonymousRoleId());
        //}

    }


    public function getSpecificProspectType($prospectType) {
        // Possible values for $prospectType => 'PROS' / 'PROSLI' --> 'Full Register Prospect' / 'Prospect Light'
        if ($prospectType == 'PROS') {
            return $this->getFullProspectType();
        }
        if ($prospectType == 'PROSLI') {
            return $this->getLightProspectType();
        }
    }


    public function getFullProspectType() {
        return self::PROSPECT_TYPE_FULL;
    }


    public function getLightProspectType() {
        return self::PROSPECT_TYPE_LIGHT;
    }
}