<?php
class Schracklive_SchrackCatalog_Model_NoSuchProductException extends Exception {
    private $sku;
    private $messageFormat = 'No such product as %s';

    public function __construct ( $sku ) {
        parent::__construct(sprintf($this->messageFormat,$sku));
        $this->sku = $sku;
    }

    public function getSku () {
        return $this->sku;
    }

    public function getMessageFormat () {
        return $this->messageFormat;
    }
}