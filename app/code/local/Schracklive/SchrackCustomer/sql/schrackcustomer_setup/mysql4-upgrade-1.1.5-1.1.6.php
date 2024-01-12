<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer_address', 'schrack_comments', array('type'=>'text','required'=>false,'label'=>'Comments'));

$installer->updateAttribute('customer', 'schrack_crm_role_id', array('frontend_input'=>'select','source_model'=>'schrackcustomer/entity_customer_attribute_source_role'));

$installer->updateAttribute('customer_address', 'schrack_type', array('frontend_input'=>'select','source_model'=>'schrackcustomer/entity_address_attribute_source_type', 'frontend_model'=>'schrackcustomer/entity_address_attribute_frontend_type'));

