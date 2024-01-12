<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD has_own_company_info TINYINT(1) NOT NULL DEFAULT 1;
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD uidnummer TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD dvrnummer TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD firmenbuchnummer TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD firmenbuchgericht TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD kammerzugehoerigkeit TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD geschaeftsfuehrer TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD grundlegenderichtung TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD aufsichtsbehoerde TEXT NULL DEFAULT NULL;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD vorschriften TEXT NULL DEFAULT NULL;
EOF
);

$installer->endSetup();
