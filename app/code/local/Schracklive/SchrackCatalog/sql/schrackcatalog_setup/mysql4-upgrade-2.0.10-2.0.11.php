<?php
$installer = $this;
$installer->startSetup();

$installer->run("
                    ALTER TABLE catalog_category_product ADD KEY FK_CATALOG_CATEGORY_CATEGORY_ENTITY (category_id);
                    ALTER TABLE catalog_category_product ADD CONSTRAINT FK_CATALOG_CATEGORY_CATEGORY_ENTITY FOREIGN KEY (category_id) REFERENCES catalog_category_entity (entity_id) ON DELETE CASCADE ON UPDATE CASCADE;
               ");


$installer->endSetup();

?>