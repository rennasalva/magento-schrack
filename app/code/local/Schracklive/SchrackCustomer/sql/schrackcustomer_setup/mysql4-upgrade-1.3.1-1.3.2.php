<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_acl_role_id', array('type' => 'int', 'input' => 'select', 'source' => 'schrackcustomer/entity_customer_attribute_source_aclrole', 'required' => false, 'label' => 'ACL Role'));
$installer->addAttribute('customer', 'schrack_salutatory', array('type' => 'varchar', 'required' => false, 'label' => 'Salutation'));
