<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_department', array('type'=>'varchar','required'=>false,'label'=>'Department'));
$installer->addAttribute('customer', 'schrack_newsletter', array('type'=>'int','input'=>'select','source'=>'eav/entity_attribute_source_boolean','required'=>false,'label'=>'Subscribes Newsletter?'));
$installer->addAttribute('customer', 'schrack_main_contact', array('type'=>'int','input'=>'select','source'=>'eav/entity_attribute_source_boolean','required'=>false,'label'=>'Is Main Contact?'));
$installer->addAttribute('customer', 'schrack_address_id', array('type'=>'int','required'=>false,'label'=>'Location'));
$installer->addAttribute('customer', 'schrack_crm_role_id', array('type'=>'int','required'=>false,'label'=>'Role'));
$installer->addAttribute('customer', 'schrack_comments', array('type'=>'text','required'=>false,'label'=>'Comments'));
$installer->addAttribute('customer', 'schrack_interests', array('type'=>'text','required'=>false,'label'=>'Interests'));
