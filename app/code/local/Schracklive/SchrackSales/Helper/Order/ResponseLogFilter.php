<?php

class Schracklive_SchrackSales_Helper_Order_ResponseLogFilter extends Schracklive_Schrack_Model_Soap_AbstractLogFilter {
    
    public function filterLog ( &$logText ) {
        $this->_removeElementContent('Data',$logText);
    }
}

?>
