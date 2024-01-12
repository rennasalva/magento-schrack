<?php

abstract class Schracklive_Schrack_Model_Soap_AbstractLogFilter {
    
    protected function _removeElementContent ( $elementName, &$logText, $replacementText = '(content removed for logging)' ) {
        $elementName.='>';
        $p = strpos($logText,$elementName);
        while ( $p && $logText[$p - 1] != '<' && $logText[$p - 1] != ':' ) {
            $p = strpos($logText,$elementName,$p + 1);
        }
        if ( ! $p )
            return false;
        $p += strlen($elementName);
        $q = strpos($logText,$elementName,$p);
        while ( $q && $logText[$q - 1] != '/' && $logText[$q - 1] != ':' ) {
            $q = strpos($logText,$elementName,$q + 1);
        }
        while ( $q > 0 && $logText[$q] != '<' )
            --$q;
        if ( ! $q )
            return false;
        $newLogText = substr($logText, 0,$p);
        $newLogText .= $replacementText;
        $newLogText .= substr($logText,$q);
        $logText = $newLogText;
        return $logText;
    }
    
    abstract public function filterLog ( &$logText );
}



?>
