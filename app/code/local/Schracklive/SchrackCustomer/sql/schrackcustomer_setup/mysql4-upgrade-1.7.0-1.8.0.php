<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->addAttribute('customer', 'schrack_mailinglist_types_csv', array('type'=>'text','required'=>false,'label'=>'Mailing List Types (CSV)'));

$tableName = $this->getTable('schrackcustomer/mailinglisttype');

$installer->run("
DROP TABLE IF EXISTS $tableName;
CREATE TABLE $tableName (
  `entity_id` int(11) unsigned NOT NULL auto_increment,
  `code` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `unsubscribeable` int(1) NOT NULL DEFAULT '0',
  `price_critical` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`entity_id`),
  KEY `IDX_SCHRACK_MAILINGLIST_TYPE_CODE` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");


