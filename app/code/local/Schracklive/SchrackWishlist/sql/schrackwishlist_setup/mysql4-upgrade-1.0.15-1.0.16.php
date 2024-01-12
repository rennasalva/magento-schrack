<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_category CONVERT TO CHARACTER SET utf8
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog CONVERT TO CHARACTER SET utf8
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer CONVERT TO CHARACTER SET utf8
EOF
);

$installer->endSetup();
