<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
ALTER TABLE partslist_item
    ADD referrer_url VARCHAR(255) NULL DEFAULT NULL
EOF
);
$installer->endSetup();
