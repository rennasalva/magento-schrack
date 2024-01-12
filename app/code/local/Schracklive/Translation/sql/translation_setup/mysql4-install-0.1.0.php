<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('translation')};
CREATE TABLE {$this->getTable('translation')} (
  `translation_id` int(11) unsigned NOT NULL auto_increment,
  `user_id` mediumint(9) unsigned NOT NULL,
  `module_name` VARCHAR(30) NOT NULL,
  `file` VARCHAR(50) NOT NULL,
  `string_en` text NOT NULL,
  `string_translated` text NULL,
  `locale` varchar(10) NOT NULL,
  `is_local` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_changed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_translated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`translation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();

?>