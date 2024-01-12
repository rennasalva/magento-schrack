<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE partslist ADD is_visible TINYINT NOT NULL DEFAULT 1
EOF
);

$installer->endSetup();
