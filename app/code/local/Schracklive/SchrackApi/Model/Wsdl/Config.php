<?php

class Schracklive_SchrackApi_Model_Wsdl_Config extends Mage_Api_Model_Wsdl_Config {
    
    /**
     * Removes just the tag schemaLocation="http://schemas.xmlsoap.org/soap/encoding/"
     * from the WSDL because the Progress WSDL Analyzer runs into troubles with that.
     * @return type 
     */
    public function getWsdlContent() {
        $res = parent::getWsdlContent();
        $res = str_replace('schemaLocation="http://schemas.xmlsoap.org/soap/encoding/"','', $res);
        return $res;
    }

}

?>
