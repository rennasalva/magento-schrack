<?php

use com\schrack\queue\protobuf\Message;

class Schracklive_SchrackCatalog_Model_Protoimport_SynonymsHandler extends Schracklive_SchrackCatalog_Model_Protoimport_Base {

    public function handle ( Message &$importMsg ) {
        if ( !$importMsg->hasSysnonyms() ) {
            return;
        }
        $sql = "DELETE FROM synonyms;";
        $this->_writeConnection->query($sql);
        $data = array();
        $lc2key = array();
        foreach ( $importMsg->getSysnonymsList() as $synonym ) {
            if ( $synonym->hasTerm() ) {
                $lcTerm = strtolower($synonym->getTerm());
                if ( ! isset($lc2key[$lcTerm]) ) {
                    $data[$synonym->getTerm()] = array();
                    $firstTerm = $lc2key[$lcTerm] = $synonym->getTerm();
                } else {
                    $firstTerm = $lc2key[$lcTerm];
                    if ( $synonym->getTerm() != $firstTerm ) {
                        // different upper and lower case -> add as synonym
                        $data[$firstTerm][] = $synonym->getTerm();
                    }
                }
                $data[$firstTerm] = array_merge($data[$firstTerm],$synonym->getSysnonyms());
            }
        }
        foreach ( $data as $term => $synonyms ) {
            $term   = $this->_writeConnection->quote($term);
            $others = $this->_writeConnection->quote(implode(",",array_unique($synonyms)));
            echo '.';
            $sql = "INSERT INTO synonyms SET term = $term, synonyms = $others;";
            $this->_writeConnection->query($sql);
        }
        echo PHP_EOL;
    }

    public function getSchrack2MagentoIdMap () {}
    protected function doHandle ( Message &$importMsg ) {}
    protected function delete ( $magentoId ) {}

}