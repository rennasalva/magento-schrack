<?php

/*
 * we need a primary key that is not the customer_id reference to customer_entity, because
 * otherwise magento won't recognize a new record
 */

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    alter table endcustomerpartslist_customer drop foreign key ENDCUSTOMERPARTSLIST_CUSTOMER_CUSTOMER_CUSTOMER_ID_FK
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer
        DROP PRIMARY KEY
EOF
);

$installer->run(<<<EOF
    alter table endcustomerpartslist_customer add primary key (id_key);
EOF
);

$installer->run(<<<EOF
    alter table endcustomerpartslist_customer add CONSTRAINT `ENDCUSTOMERPARTSLIST_CUSTOMER_CUSTOMER_CUSTOMER_ID_FK`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer_entity` (`entity_id`)
            ON DELETE CASCADE ON UPDATE CASCADE
EOF
);

$installer->endSetup();
