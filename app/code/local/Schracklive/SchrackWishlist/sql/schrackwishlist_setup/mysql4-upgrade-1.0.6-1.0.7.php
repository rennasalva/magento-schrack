<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
ALTER TABLE endcustomerpartslist_catalog
    ADD image_url VARCHAR(255)
EOF
);
$installer->endSetup();
