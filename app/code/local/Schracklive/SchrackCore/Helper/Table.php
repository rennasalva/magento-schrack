<?php

class Schracklive_SchrackCore_Helper_Table {
    private $_lineCounter;
    
    public function __construct() {
        $this->_lineCounter = 0;
    }

    public function getEvenOddClass() {
        return ((++$this->_lineCounter) % 2 === 0) ? 'even' : 'odd';
    }
    public function resetEvenOddClass() {
        $this->_lineCounter = 0;        
    }
    public function resetLineCounter() {
        $this->_lineCounter = 0;        
    }
    public function getLineCounter() {
        return $this->_lineCounter;
    }
    public function setLineCounter($counter) {
        $this->_lineCounter = $counter;
    }
}

?>
