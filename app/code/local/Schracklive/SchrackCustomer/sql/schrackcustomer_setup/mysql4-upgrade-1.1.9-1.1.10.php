<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->updateAttribute('customer_address', 'street', array('is_required'=>true));
$installer->updateAttribute('customer_address', 'city', array('is_required'=>true));
$installer->updateAttribute('customer_address', 'postcode', array('is_required'=>true));
$installer->updateAttribute('customer_address', 'country_id', array('is_required'=>true));

$installer->updateAttribute('customer_address', 'prefix', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'firstname', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'middlename', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'suffix', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'company', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'region', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'region_id', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'telephone', array('is_required'=>false));
$installer->updateAttribute('customer_address', 'fax', array('is_required'=>false));
