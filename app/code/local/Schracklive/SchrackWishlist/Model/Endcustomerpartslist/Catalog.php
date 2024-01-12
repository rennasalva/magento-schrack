<?php

class Schracklive_SchrackWishlist_Model_Endcustomerpartslist_Catalog extends Mage_Core_Model_Abstract {
    
    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schrackwishlist/endcustomerpartslist_catalog', 'catalog_id');
    }
    
    /**
     * Set date of last update for wishlist
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        if (!$this->getSeq()) {
            $this->getResource()->setSeq($this->_getNextSeq());
        }
        $this->setUpdatedAt(Mage::getSingleton('core/date')->gmtDate());
        return $this;
    }

    private function _getNextSeq() {
        $select = $this->_getWriteAdapter()->select()
            ->from(
                array('e' => $this->getTable('schrackwishlist/endcustomerpartslist_catalog')),
                array('seq'))
            ->where('e.entity_id>?', $lastProductId)
            ->limit(1)
            ->order('e.seq DESC');

        $result = $this->_getWriteAdapter()->fetchAll($select);
        return 0;
    }

    public function getCollection() {
        return Mage::getResourceModel('schrackwishlist/endcustomerpartslist_catalog_collection');

    }

    public function loadByUrl($url) {
        $catalog =  $this->getCollection()
            ->addFieldToFilter('url', $url);
        $x = $catalog->getSelect()->__toString();
        Mage::log($x, null, 'xian.log');
        return $catalog->getFirstItem();
    }
}
?>