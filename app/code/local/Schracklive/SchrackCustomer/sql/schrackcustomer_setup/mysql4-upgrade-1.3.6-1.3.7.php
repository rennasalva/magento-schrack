<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'updated_by',	array('type' => 'static', 'required' => false, 'label' => 'Updated By'));
$installer->addAttribute('customer', 'created_by',	array('type' => 'static', 'required' => false, 'label' => 'Created By'));
