<?php

$installer = $this;
$installer->startSetup();

// Init datetime:
$created_at = date('Y-m-d H:i:s');

$installer->run("
    INSERT INTO shop_navigation SET `type` = 'top-nav', `source` = 'typo', `content` = 'top_nav_5_typo', `active` = 1, `created_at` = '{$created_at}';
");

$installer->endSetup();