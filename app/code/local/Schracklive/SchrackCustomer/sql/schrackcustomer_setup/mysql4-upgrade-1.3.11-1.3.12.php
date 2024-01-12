<?php
// create / amend triggers for creating and deleting the login_token entries in the common db whenever we insert/delete a customer

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

// "local" db

$conn->query(<<<EOF
DROP TRIGGER IF EXISTS loginswitch_insert_customer;
EOF
);

$conn->query(<<<EOF
CREATE TRIGGER loginswitch_insert_customer BEFORE INSERT ON customer_entity 
     FOR EACH ROW BEGIN
        IF NOT EXISTS (SELECT 1 FROM magento_common.login_token WHERE email=new.email AND country_id=(SELECT value FROM core_config_data WHERE path='schrack/general/country')) THEN
            INSERT INTO magento_common.login_token (email, country_id) VALUES(new.email, (SELECT value FROM core_config_data WHERE path='schrack/general/country'));
        END IF;
     END;
EOF
);

$conn->query(<<<EOF
CREATE TRIGGER loginswitch_delete_customer BEFORE DELETE ON customer_entity 
     FOR EACH ROW BEGIN
        DELETE FROM magento_common.login_token WHERE email=old.email AND country_id=(SELECT value FROM core_config_data WHERE path='schrack/general/country');
     END;
EOF
);

$installer->endSetup(); 

