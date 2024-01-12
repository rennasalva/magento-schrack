<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `customer_tracking` (
  `uuid` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateLayerForLogin', `value` = '1';
INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateRegistrationCheckboxDSGVO', `value` = '0';
INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateRegistrationCheckboxDataProtection', `value` = '1';
INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateCheckoutCheckboxDSGVO', `value` = '0';
INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateCheckoutCheckboxDataProtection', `value` = '1';
");

$installer->endSetup();
