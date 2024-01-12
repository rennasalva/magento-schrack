<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
CREATE TABLE endcustomerpartslist_customer (
    customer_id INT(10) UNSIGNED NOT NULL PRIMARY KEY,
    id VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    address_1 VARCHAR(255),
    address_2 VARCHAR(255),
    address_3 VARCHAR(255),
    phone VARCHAR(255),
    fax VARCHAR(255),
    email VARCHAR(255),
    homepage VARCHAR(255),
    is_active tinyint NOT NULL DEFAULT 1,
    CONSTRAINT ENDCUSTOMERPARTSLIST_CUSTOMER_CUSTOMER_CUSTOMER_ID_FK FOREIGN KEY (customer_id) REFERENCES customer_entity (entity_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE INDEX (id)
)
EOF
);

$installer->run(<<<EOF
CREATE TABLE endcustomerpartslist_catalog (
    catalog_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    seq INT(10) UNSIGNED NOT NULL DEFAULT 0,
    name VARCHAR(255),
    url VARCHAR(255),
    UNIQUE INDEX (seq)
)
EOF
);
$installer->endSetup();
