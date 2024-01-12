<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';
require_once 'magento.php';

class Schracklife_Shell_Exportsales extends Mage_Shell_Abstract
{

    /** @var Schracklive_Search_Helper_ExportSales */
    var $helper = null;

    public function usageHelp()
    {
        return <<<USAGE
Usage:  sudo php -f exportSales.php [options] [optional: --store storeID --mode delta|full]

  all		Create solr request and submit to solr
  post		Post last request to solr
  build		Build request, but don't post data to solr
  help		This help


USAGE;
    }

    public function __construct()
    {
        parent::__construct();

        $this->helper = Mage::helper('search/exportSales');
        $store = $this->getArg('store');
        if ($store) {
            $this->helper->setStore($store);
        }
        $mode = $this->getArg('mode');
        if ($mode) {
            $this->helper->setMode($mode);
        }
        ini_set('memory_limit', '-1');
        error_reporting(E_ERROR | E_PARSE);
    }

    public function run()
    {
        $this->helper->_log('Using configuration:', true);
        $this->helper->_log('solr URL: ' . Mage::getStoreConfig('schrack/solr/solrserver', $this->helper->store), true);

        if ($this->getArg('all')) {
            $this->helper->runAll();
        } elseif ($this->getArg('post')) {
            $this->helper->runPost();
        } elseif ($this->getArg('build')) {
            $this->helper->runBuild();
        } else {
            echo $this->usageHelp();
        }
    }

}

$shell = new Schracklife_Shell_Exportsales();
$shell->run();
