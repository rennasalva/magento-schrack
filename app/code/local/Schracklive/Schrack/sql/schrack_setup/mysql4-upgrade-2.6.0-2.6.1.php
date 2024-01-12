<?php

$installer = $this;
$installer->startSetup();
$installer->run("

    INSERT INTO `acl_roles` SET `id` = 12, `name` = 'list_price_customer', `parent_id` = 0, `is_visible` = 1;	

");
$installer->endSetup();
