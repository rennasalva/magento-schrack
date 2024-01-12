<?php

class Schracklive_SchrackCatalog_Helper_Megamenu extends Mage_Core_Helper_Abstract {

    public function getMegamenuChangedTimestampText () {
        $registryKey = 'megamenu_timestamp';
        $tsMegaMenu = Mage::registry($registryKey);
        if ( ! $tsMegaMenu ) {
            // read value from DB to avoid older cached value
            $sql = "SELECT value FROM core_config_data WHERE path = 'schrack/performance/mega_menu_latest_refresh_datetime'";
            $tsMegaMenu = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql);
            Mage::register($registryKey,$tsMegaMenu);
        }
        return $tsMegaMenu;
    }

    public function getMegamenuChangedTimestamp () {
        return strtotime($this->getMegamenuChangedTimestampText());
    }

    public function setPillarFromCategory($category = null) {
        if (!isset($category)) {
            $pillar = null;
        } else {

            while (intval($category->getLevel() > 2)) {
                $category = Mage::getModel('schrackcatalog/category')->load($category->getParentId());
            }
            $pillar = $category->getSchrackStrategicPillar();
        }

        $session = Mage::getModel('core/session');
        $session->setSchrackStrategicPillar($pillar);
    }

    public function setPillarFromProduct($product) {
        $this->setPillarFromCategory($product->getCategory());
    }
}

?>
