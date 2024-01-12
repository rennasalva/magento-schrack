<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->addAttribute('customer', 'list_pager_limit', array('type' => 'int', 'default' => '10', 'required' => false, 'label' => 'List Pager Limit'));

$installer->endSetup();
