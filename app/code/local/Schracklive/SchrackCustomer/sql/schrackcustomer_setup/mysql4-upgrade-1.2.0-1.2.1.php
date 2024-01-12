<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_emails', array('type'=>'text','required'=>false,'label'=>'Additional Email Addresses'));
