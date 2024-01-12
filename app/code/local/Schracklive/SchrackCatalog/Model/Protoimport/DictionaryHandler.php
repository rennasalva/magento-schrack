<?php

use com\schrack\queue\protobuf\Message;

class Schracklive_SchrackCatalog_Model_Protoimport_DictionaryHandler extends Schracklive_SchrackCatalog_Model_Protoimport_Base {

    public function handle ( Message &$importMsg ) {
        if ( !$importMsg->hasDictionary() ) {
            return;
        }
        $sql = "DELETE FROM schrack_dictionary;";
        $this->_writeConnection->query($sql);
        $data = array();
        foreach ( $importMsg->getDictionaryList() as $dictionary ) {
            if ( $dictionary ) {
                $data[] = $dictionary;
            }
        }
        foreach ( $data as $index => $term ) {
            $term  = $this->_writeConnection->quote($term);
            echo '.';
            $now = date("Y-m-d H:i:s");
            $sql = "INSERT IGNORE INTO schrack_dictionary SET term = $term, created_at ='" . $now . "';";
            $this->_writeConnection->query($sql);
        }
        echo PHP_EOL;
    }

    public function getSchrack2MagentoIdMap () {}
    protected function doHandle ( Message &$importMsg ) {}
    protected function delete ( $magentoId ) {}

}
