<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('wws_signal')};
CREATE TABLE {$this->getTable('wws_signal')} (
  `signal_id` int(10) unsigned NOT NULL auto_increment,
  `code` char(3) NOT NULL default '',
  `wws_message` varchar(255) NOT NULL default '',
  `message` varchar(255) NOT NULL default '',
  `change_recreate` tinyint(1) NOT NULL default '0',
  `change_mail` tinyint(1) NOT NULL default '0',
  `change_mail_subject` varchar(255) NOT NULL default '',
  `change_mail_body` mediumtext,
  `ship_recreate` tinyint(1) NOT NULL default '0',
  `ship_mail` tinyint(1) NOT NULL default '0',
  `ship_mail_subject` varchar(255) NOT NULL default '',
  `ship_mail_body` mediumtext,
  PRIMARY KEY (`signal_id`),
  KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();
