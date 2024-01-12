<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    CREATE TABLE sales_requestreceiver (
        receiver_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL
);
EOF
);

$installer->endSetup();
