<?php

require_once dirname(dirname(__FILE__)).'/abstract.php';
require_once 'magento.php';

class Schracklife_Shell_Exportproducts extends Mage_Shell_Abstract {

	/** @var Schracklive_Search_Helper_Export */
    var $helper = null;

    var $query = '';
    var $postToSolr = false;

	public function usageHelp() {
		return <<<USAGE
Usage:  sudo php -f exportProducts.php [options] [optional: --store storeID]

  all		 Create solr request, facet config and submit to solr
  post		 Post last request to solr
  build		 Build requests and config files, but don't post data to solr
  debug 	 Just print out what would get saved and posted to solr
        	 [optional: --query "e.sku = 'AS181040-5'"; DB query only works for static product attributes]
  synonyms	 Update the synonyms
  dictionary Update the dictionary
  facets	 Update the category facets
  help		 This help


USAGE;
	}

	public function  __construct() {
		parent::__construct();

        $this->helper = Mage::helper('search/export');
        $store = $this->getArg('store');
		if ($store) {
            $this->helper->setStore($store);
		}
		$query = $this->getArg('query');
		if ($query && is_string($query)) {
			$this->query = $query;
		}

		ini_set('memory_limit', '-1');
		error_reporting(E_ERROR | E_PARSE);
	}

	public function run() {
		if (intval(Mage::getStoreConfig('schrack/solr/solrexport_active', $this->helper->store)) == 1 ) {
			$this->helper->_log('Using configuration:', true);
			$this->helper->_log('solr URL: '.Mage::getStoreConfig('schrack/solr/solrserver', $this->helper->store), true);

			if ($this->getArg('all')) {
				$this->helper->runAll();
			} elseif ($this->getArg('post')) {
				$this->helper->runPost();
			} elseif ($this->getArg('build')) {
				$this->helper->runBuild();
			} elseif ($this->getArg('debug')) {
				$this->helper->runDebug($this->query);
			} elseif ($this->getArg('synonyms')) {
				$this->helper->runSynonyms();
			} elseif ($this->getArg('dictionary')) {
				$this->helper->runDictionary();
			} elseif ($this->getArg('facets')) {
				$this->helper->runFacets();
			} else {
				echo $this->usageHelp();
			}
		} else {
			$this->helper->_log('SOLR Export Deactivated', true);
		}
	}

}

$shell = new Schracklife_Shell_Exportproducts();
$shell->run();

