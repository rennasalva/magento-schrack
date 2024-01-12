<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer CHANGE address_1 address1 VARCHAR(255) NULL DEFAULT NULL
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer CHANGE address_2 address2 VARCHAR(255) NULL DEFAULT NULL
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer CHANGE address_3 address3 VARCHAR(255) NULL DEFAULT NULL
EOF
);

$installer->endSetup();
