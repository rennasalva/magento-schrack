<?php

class Schracklive_SchrackCatalogSearch_Helper_Data extends Mage_CatalogSearch_Helper_Data {

	public function getResultUrl($query = null) {
		$url_typo3 = Mage::getStoreConfig('schrack/typo3/typo3url');
		$url_typo3_search = Mage::getStoreConfig('schrack/typo3/typo3searchurl');
		$search_url = '';

		if (strlen($url_typo3) && strlen($url_typo3_search)) {
			$search_url = $url_typo3.$url_typo3_search;
		}
		if (Mage::app()->getRequest()->isSecure()) {
			$search_url = str_replace('http:', 'https:', $search_url);
		}
		return $search_url;
	}

	public function getSuggestions() {
		return Mage::helper('catalogsearch')->getSuggestCollection();
	}

	public function getProductCollection(Mage_Catalog_Model_Category $category) {
		return null;
	}

	public function getQueryFromRequest() {
		$query = '';
		$queryStr = '';
		$queryTerms = array();
		$searchTerm = trim(preg_replace('/\s+/', ' ', Mage::app()->getRequest()->getParam('q')));
        $searchTerm = $this->filterParamValue($searchTerm);
		if (!empty($searchTerm)) {
			$queryStr = $this->queryToString($searchTerm);
		}
		return $queryStr;
	}

	public function queryToString($searchTerm) {
		$searchTerm = strtolower(stripcslashes($searchTerm));
		$prefixMap = array(
			'+' => ' AND ',
			'-' => ' NOT '
		);
		$queryTerms = array();
		if (!preg_match_all('/[+|-]?"(?:\\\\.|[^\\\\"])*|\S+/', $searchTerm, $queryTerms)) {
			return '';
		}
		$parsedTerms = array();
		$cntValidTerms = 0;
		foreach ($queryTerms[0] as $queryTerm) {
			$queryTerm = trim(stripslashes($queryTerm));
			$prefix = $queryTerm{0};
			if ($prefix == "+" || $prefix == "-") {
				$queryTerm = substr($queryTerm, 1);
			} else {
				$prefix = "+";
			}
			$unqoted = trim($queryTerm, '"');
			if (!$unqoted) {
				continue;
			}
			$escaped = $this->escapeSpecialCharacters($unqoted);
			// Only add wildcard chars or quotes if term contains no wildcard
			if (strstr($escaped, '*') === false) {
				$finalTerm = '("'.$escaped.'" OR '.$escaped.'*)';
			} else {
				$finalTerm = $escaped;
			}
			if ($cntValidTerms > 0) {
				$parsedTerms[] = $prefixMap[$prefix].$finalTerm;
			} else {
				$parsedTerms[] = $finalTerm;
			}
			$cntValidTerms++;
		}

		return (implode(' ', $parsedTerms));
	}

	public function escapeSpecialCharacters($value) {
		// list taken from http://lucene.apache.org/java/3_3_0/queryparsersyntax.html#Escaping%20Special%20Characters
		// not escaping *, &&, ||, ?, -, !, + though
		$pattern = '/(\(|\)|\{|}|\[|]|\^|"|~|:|\\\)/';
		$replace = '\\\$1';

		return preg_replace($pattern, $replace, $value);
	}

    private function filterParamValue($inputParamValue) {
        $filteredInputParamValue = str_replace('"', "", $inputParamValue);
        $filteredInputParamValue = str_replace("'", "", $filteredInputParamValue);
        $filteredInputParamValue = filter_var($filteredInputParamValue, FILTER_SANITIZE_STRING);
        $filteredInputParamValue = str_replace(array('<', '>', 'alert', 'script', '(', ')'), '', stripslashes($filteredInputParamValue));

        return $filteredInputParamValue;
    }

}
