<?php
$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
$query = "SELECT value FROM core_config_data WHERE path LIKE 'schrack/general/country'";
$country = $readConnection->fetchOne($query);

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

$installer->run("
ALTER TABLE `schrack_promotion_product` DROP FOREIGN KEY `FK_SCHRACK_PROMOTION_PRODUCT_PROMOTION_ID`;
ALTER TABLE `schrack_promotion_account` DROP FOREIGN KEY `FK_SCHRACK_PROMOTION_ACCOUNT_PROMOTION_ID`;
ALTER TABLE `schrack_promotion` MODIFY entity_id INTEGER;
ALTER TABLE `schrack_promotion_product` MODIFY promotion_id INTEGER;
ALTER TABLE `schrack_promotion_account` MODIFY promotion_id INTEGER;
ALTER TABLE `schrack_promotion_account` ADD CONSTRAINT FK_SCHRACK_PROMOTION_ACCOUNT_PROMOTION_ID FOREIGN KEY (`promotion_id`) REFERENCES `schrack_promotion` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `schrack_promotion_product` ADD CONSTRAINT FK_SCHRACK_PROMOTION_PRODUCT_PROMOTION_ID FOREIGN KEY (`promotion_id`) REFERENCES `schrack_promotion` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO core_config_data SET `path` = 'schrack/promotions/stomp_url', `value` = 'tcp://seviemq1.at.schrack.lan:61613';
INSERT INTO core_config_data SET `path` = 'schrack/promotion_books/pathBeforeCountry', `value` = '';
INSERT INTO core_config_data SET `path` = 'schrack/promotion_books/pathAfterCountry', `value` = '';
INSERT INTO core_config_data SET `path` = 'schrack/promotions/service_url', `value` = 'https://ddm.schrack.com/get/products';
INSERT INTO core_config_data SET `path` = 'schrack/promotions/message_queue_error', `value` = 'spt_to_ws_error';
INSERT INTO core_config_data SET `path` = 'schrack/promotions/message_queue_inbound', `value` = 'spt_to_ws';
INSERT INTO core_config_data SET `path` = 'schrack/promotions/service_user', `value` = 'shop-'{$country};
INSERT INTO core_config_data SET `path` = 'schrack/promotions/service_password', `value` = '';
INSERT INTO core_config_data SET `path` = 'schrack/promotions/default_image_url', `value` = '';
");

$installer->endSetup();
