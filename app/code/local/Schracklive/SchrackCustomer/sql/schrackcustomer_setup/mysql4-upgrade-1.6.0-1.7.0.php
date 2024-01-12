<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();


$dfltImageLink = 'http://image.schrack.com/foto/f_kab_' . strtolower(Mage::getStoreConfig('schrack/general/country')) . '.jpg';

$installer->run("
ALTER TABLE `promotion_book` ADD COLUMN `id`             int(10) unsigned FIRST;
ALTER TABLE `promotion_book` DROP PRIMARY KEY;
ALTER TABLE `promotion_book` CHANGE id id INT(10) AUTO_INCREMENT PRIMARY KEY;
");
$installer->run("
ALTER TABLE `promotion_book` ADD COLUMN `contact_number` int(11)   DEFAULT NULL AFTER `customer_id`;
ALTER TABLE `promotion_book` ADD COLUMN `link`           text DEFAULT NULL AFTER `file_name`;
ALTER TABLE `promotion_book` ADD COLUMN `mailinglist_id` int(11)   NOT NULL DEFAULT -1 AFTER `contact_number`;
");
$installer->run("
ALTER TABLE `promotion_book` MODIFY COLUMN `file_name`   varchar(255) DEFAULT NULL;  
");
$installer->run("
ALTER TABLE `promotion_book` ADD CONSTRAINT UNQ_PROMOTION_BOOK UNIQUE (`customer_id`, `contact_number`, `mailinglist_id`);
ALTER TABLE `promotion_book` ADD INDEX      NDX_USER_CONTACT          (`customer_id`, `contact_number`);
");
$installer->run("
CREATE TABLE IF NOT EXISTS `promotion_book_image` (
  `mailinglist_id` int(11) NOT NULL,
  `image_link`     text    NOT NULL,
  PRIMARY KEY (`mailinglist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `promotion_book_image` (`mailinglist_id`,`image_link`) values(-1,'$dfltImageLink');  
");

$promotionBookHelper = Mage::helper('schrackcustomer/promotionbook');
$connection = $installer->getConnection();
$select = $connection->select()->from('promotion_book');
$tmp = array();
foreach ( $connection->fetchAll($select) as $row ) {
    $id = $row['id'];
    $fileName = $row['file_name'];
    $link = $promotionBookHelper->fileName2link($fileName);
    $tmp[$id] = $link;
}
foreach ( $tmp as $id => $link ) {
    $sql = "UPDATE `promotion_book` SET `link` = '$link' WHERE `id` = $id;";
    $installer->run($sql);
}
$installer->run("
ALTER TABLE `promotion_book` DROP COLUMN `file_name`; 
");

$installer->endSetup();

