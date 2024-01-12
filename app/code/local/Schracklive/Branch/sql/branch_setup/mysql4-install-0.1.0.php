<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('branch')};
CREATE TABLE {$this->getTable('branch')} (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` smallint(5) unsigned NOT NULL,
  `warehouse_id` smallint(5) unsigned NOT NULL,
  `created_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`entity_id`),
  UNIQUE KEY `u_branch` (`branch_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ");

$installer->endSetup();

?>