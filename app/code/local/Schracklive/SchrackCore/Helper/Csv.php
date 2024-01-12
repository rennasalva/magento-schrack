<?php

/**
 * Utility methods for working on csvs
 *
 * @author c.friedl
 */
class Schracklive_SchrackCore_Helper_Csv {
    /**
     * given a list of possible delimiters, find the one that's first in the string
     * @param string $line the csv line
     * @param array $delims array of delimiter characters
     * @throws Exception
     */
    public function determineDelimiter($line, array $delims = null) {
        // function moved to other csv helper; keeping this one only for backward compatibility
        return Mage::helper('schrack/csv')->determineDelimiter($line,$delims);
    }
}

?>
