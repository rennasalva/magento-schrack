<?php
$installer = $this;
$installer->startSetup();

$installer->run("
                    ALTER TABLE sales_flat_order_schrack_index ADD KEY FK_ORDER_ENTITY (order_id);
                    ALTER TABLE sales_flat_order_schrack_index ADD CONSTRAINT FK_ORDER_ENTITY FOREIGN KEY (order_id) REFERENCES sales_flat_order (entity_id) ON DELETE CASCADE ON UPDATE CASCADE;
                    ALTER TABLE sales_flat_order_schrack_index_position ADD KEY FK_PARENT_ENTITY (parent_id);
                    ALTER TABLE sales_flat_order_schrack_index_position ADD CONSTRAINT FK_PARENT_ENTITY FOREIGN KEY (parent_id) REFERENCES sales_flat_order_schrack_index (entity_id) ON DELETE CASCADE ON UPDATE CASCADE;
               ");


$installer->endSetup();

?>