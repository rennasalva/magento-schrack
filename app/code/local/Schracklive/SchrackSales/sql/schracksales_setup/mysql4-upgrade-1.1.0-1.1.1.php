<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */

$installer->updateAttribute('order_address', 'street', array('is_required'=>true));
$installer->updateAttribute('order_address', 'city', array('is_required'=>true));
$installer->updateAttribute('order_address', 'postcode', array('is_required'=>true));
$installer->updateAttribute('order_address', 'country_id', array('is_required'=>true));

$installer->updateAttribute('order_address', 'prefix', array('is_required'=>false));
$installer->updateAttribute('order_address', 'firstname', array('is_required'=>false));
$installer->updateAttribute('order_address', 'middlename', array('is_required'=>false));
$installer->updateAttribute('order_address', 'suffix', array('is_required'=>false));
$installer->updateAttribute('order_address', 'company', array('is_required'=>false));
$installer->updateAttribute('order_address', 'region', array('is_required'=>false));
$installer->updateAttribute('order_address', 'region_id', array('is_required'=>false));
$installer->updateAttribute('order_address', 'telephone', array('is_required'=>false));
$installer->updateAttribute('order_address', 'fax', array('is_required'=>false));
