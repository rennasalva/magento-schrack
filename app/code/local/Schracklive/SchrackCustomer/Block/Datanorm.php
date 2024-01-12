<?php

class Schracklive_SchrackCustomer_Block_Datanorm extends Mage_Core_Block_Template {
    
    protected function getMassuploadPostUrl() {            
        $url = $this->getUrl('*/*/post');        
        return $url;
    }
}

?>
