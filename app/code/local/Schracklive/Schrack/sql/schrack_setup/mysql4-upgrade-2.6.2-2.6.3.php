<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();
$installer->run("

    DROP TABLE IF EXISTS mail_alerts;
    
    CREATE TABLE mail_alerts (
      `message` VARCHAR(255) NOT NULL,
      `last_occurrence_time` TIMESTAMP NOT NULL,
      `last_email_time` TIMESTAMP NOT NULL,
      `occurrences_since_last_mail` INT(12) DEFAULT 0,
      PRIMARY KEY  (`message`)
    ) ENGINE=INNODB DEFAULT CHARSET=UTF8;

");

$installer->endSetup();