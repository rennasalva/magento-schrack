<?php

class Schracklive_SchrackCore_Helper_Json {
    private $_document;
    /**
     * transform an indexed json-array with subarrays to xml
     * 
     * @param DOMDocument $document
     * @param array $array
     */
    public function toXML($array, $addIds = true) {
        $this->_document = new DOMDocument();
        $this->_toXML($array, $addIds);
        return $this->_document;
    }
    
    private function _toXML($array, $addIds, $root = null) {
        $i = 0;
        if ($root === null)
            $root = $this->_document->createElement('root');
        foreach($array as $key => $value) {
            if (is_numeric($key))
                $key = $root->nodeName . '-' . $i;
            if (is_object($value)) {
                $ar = (array)$value;
                $element = $this->_document->createElement($key);
                $this->_toXML($ar, $addIds, $element);
                $root->appendChild($element);
            } else if (is_array($value)) {
                $ar = $value;
                $element = $this->_document->createElement($key);
                $this->_toXML($ar, $addIds, $element);
                $root->appendChild($element);
            } else {
                if ($addIds && $i === 0) {
                   $id = $this->_document->createAttribute($key);
                   $id->value = $value;
                   $root->appendChild($id);
                }
                $element = $this->_document->createElement($key);
                $text = $this->_document->createTextNode($value);
                $element->appendChild($text);
                $root->appendChild($element);
            }
            ++$i;
        }
        $this->_document->appendChild($root);
    }    
}

?>
