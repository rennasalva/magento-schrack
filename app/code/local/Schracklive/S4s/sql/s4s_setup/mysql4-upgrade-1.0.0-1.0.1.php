<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    ALTER TABLE customer_entity ADD INDEX NDX_S4S_ID (schrack_s4s_id);
    ALTER TABLE customer_entity ADD INDEX NDX_S4S_NICKNAME (schrack_s4s_nickname);
");
$installer->endSetup();
