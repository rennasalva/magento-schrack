<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateRegistrationCheckboxAGB', `value` = '0';
    INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateCheckoutCheckboxUserTerms', `value` = '0';
    INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateRegistrationCheckboxUserTerms', `value` = '0';
    INSERT INTO core_config_data SET `path` = 'schrack/dsgvo/activateUserTermsFeature', `value` = '0';
");

$installer->endSetup();
