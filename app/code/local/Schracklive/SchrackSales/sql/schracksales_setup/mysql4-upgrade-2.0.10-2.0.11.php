<?php
$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$installer->run("
                    DROP TABLE IF EXISTS tmp;
                    CREATE TABLE tmp (id int unsigned);
                    INSERT INTO tmp SELECT max(entity_id) FROM sales_flat_order_schrack_index
                    GROUP BY order_id, shipment_id, invoice_id, credit_memo_id, is_offer, is_order_confirmation, is_processing
                    HAVING count(*) > 1;
                    DELETE FROM sales_flat_order_schrack_index where entity_id IN (SELECT id from tmp);
                    DROP TABLE tmp;
               ");

$installer->run("
                    ALTER TABLE sales_flat_order_schrack_index ADD unique_nullable_ids VARCHAR(80) NOT NULL DEFAULT '-';
               ");

$connection->query(<<<EOF
                    CREATE TRIGGER `sales_flat_order_schrack_index_before_insert` BEFORE INSERT ON `sales_flat_order_schrack_index`
                    FOR EACH ROW BEGIN
                        SET NEW.unique_nullable_ids = CONCAT(IFNULL(NEW.shipment_id,''), '-', IFNULL(NEW.invoice_id, ''), '-', IFNULL(NEW.credit_memo_id, ''));
                    END;
EOF
);

$connection->query(<<<EOF
                    CREATE TRIGGER `sales_flat_order_schrack_index_before_update` BEFORE UPDATE ON `sales_flat_order_schrack_index`
                    FOR EACH ROW BEGIN
                        IF NEW.shipment_id != OLD.shipment_id OR NEW.invoice_id != OLD.invoice_id OR NEW.credit_memo_id != OLD.credit_memo_id THEN
                            SET NEW.unique_nullable_ids = CONCAT(IFNULL(NEW.shipment_id,''), '-', IFNULL(NEW.invoice_id, ''), '-', IFNULL(NEW.credit_memo_id, ''));
                        END IF;
                    END;
EOF
               );

$installer->run("
                    UPDATE sales_flat_order_schrack_index SET unique_nullable_ids = CONCAT(IFNULL(shipment_id,''), '-', IFNULL(invoice_id, ''), '-', IFNULL(credit_memo_id, ''));
               ");

$installer->run("
                    ALTER TABLE sales_flat_order_schrack_index ADD UNIQUE INDEX UNIQUE_IDENTIY (wws_customer_id, order_id, unique_nullable_ids, is_offer, is_order_confirmation, is_processing);
               ");

$installer->endSetup();

?>