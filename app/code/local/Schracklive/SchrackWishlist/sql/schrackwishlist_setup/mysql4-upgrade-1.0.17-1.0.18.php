<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog DROP INDEX seq;
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog ADD UNIQUE INDEX (category_id, seq);
EOF
);

$installer->endSetup();
