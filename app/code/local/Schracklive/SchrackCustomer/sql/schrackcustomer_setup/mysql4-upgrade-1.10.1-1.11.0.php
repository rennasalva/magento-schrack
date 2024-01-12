<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

/*
 * Correct way of implementing new attribute to customer
 * */


$entityTypeId     = $installer->getEntityTypeId('customer');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('customer', 'schrack_customer_favorite_list', array(
    'input'         => 'text',
    'type'          => 'text',
    'label'         => 'Customer Favorite List',
    'visible'       => 1,
    'required'      => 1,
    'default'       => '{customers: []}',
    'user_defined'  => 1
));

$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'schrack_customer_favorite_list',
    '999'  //sort_order
);

$oAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'schrack_customer_favorite_list');
$oAttribute->setData('used_in_forms', array('adminhtml_customer'));

$oAttribute->save();

