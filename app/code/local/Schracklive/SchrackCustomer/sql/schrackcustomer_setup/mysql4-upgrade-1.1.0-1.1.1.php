<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_pickup', array('type'=>'int','required'=>false,'label'=>'Default Pickup Location'));