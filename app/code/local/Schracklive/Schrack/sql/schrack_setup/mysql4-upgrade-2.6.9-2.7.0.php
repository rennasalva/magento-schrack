<?php

$installer = $this;
$installer->startSetup();

$installer->run("    
    INSERT INTO core_config_data SET `path` = 'schrack/toolsma/multiple_advisor_feature_advisor_one_mobile', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/toolsma/multiple_advisor_feature_advisor_two_mobile', `value` = '';
    INSERT INTO core_config_data SET `path` = 'schrack/toolsma/multiple_advisor_feature_advisor_three_mobile', `value` = '';    
");

$installer->endSetup();
