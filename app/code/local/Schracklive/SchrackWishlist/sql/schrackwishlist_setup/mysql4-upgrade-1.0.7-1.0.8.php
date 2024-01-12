<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
ALTER TABLE endcustomerpartslist_customer
    CHANGE id id_key VARCHAR(255) NOT NULL
EOF
);
$installer->endSetup();
