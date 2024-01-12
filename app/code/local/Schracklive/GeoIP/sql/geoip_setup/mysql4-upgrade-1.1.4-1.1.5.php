<?php

$installer = $this;

$installer->startSetup();

$installer->run("

    INSERT INTO `core_config_data` (`scope`, `scope_id`, `path`, `value`, `updated_at`) VALUES ('default', '0', 'schrack/general/loggedIn/noPrices', 'RU', now());
");

$installer->endSetup();