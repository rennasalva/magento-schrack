<?php

class Schracklive_SchrackWishlist_Model_Endcustomerpartslist_Category extends Mage_Core_Model_Abstract {
    
    public function __construct() {
        parent::__construct();
        $this->_setResourceModel('schrackwishlist/endcustomerpartslist_category', 'category_id');
    }
    
    /**
     * Set date of last update for wishlist
     *
     * @return Schracklive_SchrackWishlist_Model_Endcustomerpartslist_Category
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
                array('e' => $this->getTable('schrackwishlist/endcustomerpartslist_category')),
                array('seq'))
            ->where('e.entity_id>?', $lastProductId)
            ->limit(1)
            ->order('e.seq DESC');

        $result = $this->_getWriteAdapter()->fetchAll($select);
        return 0;
    }

    public function getCollection() {
        return Mage::getResourceModel('schrackwishlist/endcustomerpartslist_category_collection');

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