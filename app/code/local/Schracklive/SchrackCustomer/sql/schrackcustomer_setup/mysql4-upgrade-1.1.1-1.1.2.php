<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_noorderauthorization', array('type'=>'int','required'=>false,'default'=>0,'label'=>'noOrderAuthorization'));
$installer->addAttribute('customer', 'schrack_hideprices', array('type'=>'int','required'=>false,'default'=>0,'label'=>'hidePrices'));