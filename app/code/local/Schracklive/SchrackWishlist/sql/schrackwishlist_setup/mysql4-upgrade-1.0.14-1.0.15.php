<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD welcome_url VARCHAR(255) NULL DEFAULT NULL
EOF
);

$installer->run(<<<EOF
    CREATE TABLE endcustomerpartslist_category (
        category_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        seq INT UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        UNIQUE KEY (seq)
    )
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog ADD category_id INT UNSIGNED NOT NULL DEFAULT 1
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog ADD KEY (category_id)
EOF
);

/* TODO: can only be added after we know the references...
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_catalog ADD CONSTRAINT `ENDCUSTOMERPARTSLIST_CATALOG_CATEGORY_ID_FK`
        FOREIGN KEY (`category_id`)
        REFERENCES `endcustomerpartslist_category` (`category_id`)
            ON DELETE CASCADE ON UPDATE CASCADE
EOF
);

$installer->endSetup();
