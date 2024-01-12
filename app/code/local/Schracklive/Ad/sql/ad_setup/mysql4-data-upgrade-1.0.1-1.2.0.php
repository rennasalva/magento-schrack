<?php

$installer = $this;
$installer->startSetup();
$installer->run("

	INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'schrack/ad/use_ssl2', '1');
    UPDATE `core_config_data` SET value = '3269' WHERE path = 'schrack/ad/port2';

");
$installer->endSetup();
