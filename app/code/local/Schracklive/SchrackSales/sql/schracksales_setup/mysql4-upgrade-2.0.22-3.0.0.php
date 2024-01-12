<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    CREATE TABLE sales_schrack_ordered_offer (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        customer_number VARCHAR(255) NOT NULL,
        order_number VARCHAR(255) NOT NULL
    );
    CREATE INDEX ndx_sales_schrack_ordered_offer_customer ON sales_schrack_ordered_offer (customer_number);
    CREATE INDEX ndx_sales_schrack_ordered_offer_customer_order ON sales_schrack_ordered_offer (customer_number,order_number);
EOF
);

$installer->endSetup();
