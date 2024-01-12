<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_CleanupAttributes extends Mage_Shell_Abstract
{
    private $readConnection, $writeConnection;

    function __construct ()
    {
        parent::__construct();
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function run () {
        echo "cleaning options...";
        $sql = " DELETE o, v"
             . " FROM `eav_attribute` a"
             . " INNER JOIN `eav_attribute_option` o ON a.`attribute_id` = o.`attribute_id`"
             . " INNER JOIN `eav_attribute_option_value` v ON v.`option_id` = o.`option_id`"
             . " INNER JOIN `eav_entity_type` t ON t.`entity_type_id` = a.`entity_type_id`"
             . " LEFT JOIN `catalog_product_entity_int` pi ON o.`option_id` = pi.`value` AND o.`attribute_id` = pi.`attribute_id`"
             . " LEFT JOIN `catalog_product_entity_varchar` pv ON FIND_IN_SET(o.`option_id`, pv.`value`) AND o.`attribute_id` = pv.`attribute_id`"
             . " WHERE a.`backend_type` <> 'static'"
             . " AND a.`is_user_defined` = 1"
             . " AND pi.`entity_id` IS NULL"
             . " AND pv.`entity_id` IS NULL"
             . " AND t.`entity_type_code` = 'catalog_product';";
        $this->writeConnection->query($sql);
        echo "ok" . PHP_EOL;

        $donts = "'" . implode("','",Schracklive_SchrackCatalog_Model_Protoimport_ArticlesHandler::$UNDYNAMIC_SCHRACK_ATTRIBUTES) . "'";

        echo "cleaning attributes...";
        $sql = " DELETE a, ca FROM eav_attribute a"
             . " INNER JOIN `catalog_eav_attribute` ca ON a.`attribute_id` = ca.`attribute_id`"
             . " INNER JOIN `eav_entity_type` t ON t.`entity_type_id` = a.`entity_type_id`"
             . " LEFT JOIN `catalog_product_entity_varchar` pv ON a.`attribute_id` = pv.`attribute_id`"
             . " WHERE a.`backend_type` = 'varchar'"
             . " AND `attribute_code` NOT IN ($donts)"
             . " AND a.`is_user_defined` = 1"
             . " AND pv.`entity_id` IS NULL"
             . " AND t.`entity_type_code` = 'catalog_product';";
        $this->writeConnection->query($sql);
        echo "ok" . PHP_EOL;

        echo 'done.' . PHP_EOL;
    }

}

(new Schracklive_Shell_CleanupAttributes())->run();
