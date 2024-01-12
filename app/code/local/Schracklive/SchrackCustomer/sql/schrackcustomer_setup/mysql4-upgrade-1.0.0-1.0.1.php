<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_advisor_principal_name', array('type'=>'varchar','required'=>false,'label'=>'Advisor'));
$installer->addAttribute('customer', 'schrack_advisors_principal_names', array('type'=>'text','required'=>false,'label'=>'Additional Advisors'));

$installer->removeAttribute('customer', 'schrack_advisor_id');
