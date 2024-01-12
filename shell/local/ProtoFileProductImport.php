<?php

require_once 'shell.php';

class Schracklive_Shell_ProtoFileProductImport extends Schracklive_Shell {

    /* @var $_importer Schracklive_SchrackCatalog_Model_Protoimport */
    var $_importer;
    var $_importFile;
    var $_overridePackageType = null;
    var $_dumpFile = false;
    var $_dumpStruct = false;
    var $_serialize = false;
    var $_unserialize = false;
    var $_sku = null;

    public function __construct($tempDir = null) {
        parent::__construct();
        if ($this->getArg('override_package_type')) {
            $this->_overridePackageType = $this->getArg('override_package_type');
        }
        if ($this->getArg('file')) {
            $this->_importFile = $this->getArg('file');
            if ( ! file_exists($this->_importFile) ) {
                die("File '$this->_importFile' not found!" . PHP_EOL);
            }
            if ($this->getArg('sku')) {
                $this->_sku = $this->getArg('sku');
            }
            if ($this->getArg('dump')) {
                $this->_dumpFile = true;
            }
            if ($this->getArg('dump_struct')) {
                $this->_dumpStruct = true;
            }
            if ($this->getArg('serialize')) {
                $this->_serialize = true;
            }
        } else if ($this->getArg('unserializeAndRun')) {
            $this->_unserialize = true;
        } else if ($this->getArg('help')) {
            throw new ShowHelpException();
        }

        /** @noinspection SpellCheckingInspection */
        $this->_importer = Mage::getModel('schrackcatalog/protoimport');
        
        if ($this->getArg('no_groups')) {
            $this->_importer->disableGroupProcessing();
        }
        if ($this->getArg('no_articles')) {
            $this->_importer->disableArticleProcessing();
        }
        if ($this->getArg('no_relations')) {
            $this->_importer->disableRelationProcessing();
        }
        if ($this->getArg('no_reindex')) {
            $this->_importer->disableReindexing();
        }
        if ($this->getArg('no_solr')) {
            $this->_importer->disableSolrExport();
        }
        if ($this->getArg('no_megamenu')) {
            $this->_importer->disableMegaMenuUpdate();
        }
        if ($this->getArg('no_cachewarmup')) {
            $this->_importer->disableWarmupCache();
        }
        if ($this->getArg('only_groups')) {
            $this->_importer->disableAll();
            $this->_importer->disableGroupProcessing(false);
        }
        if ($this->getArg('only_articles')) {
            $this->_importer->disableAll();
            $this->_importer->disableArticleProcessing(false);
        }
        if ($this->getArg('only_relations')) {
            $this->_importer->disableAll();
            $this->_importer->disableRelationProcessing(false);
        }
        if ($this->getArg('only_reindex')) {
            $this->_importer->disableAll();
            $this->_importer->disableReindexing(false);
        }
        if ($this->getArg('only_solr')) {
            $this->_importer->disableAll();
            $this->_importer->disableSolrExport(false);
        }
        if ($this->getArg('finalize')) {
            $this->_importer->enableFinalize();
        }
        if ($this->getArg('only_megamenu')) {
            $this->_importer->disableAll();
            $this->_importer->disableMegaMenuUpdate(false);
        }
        if ($this->getArg('only_cachewarmup')) {
            $this->_importer->disableAll();
            $this->_importer->disableWarmupCache(false);
        }

        if ( $this->getArg('only_create_saved_group_urls') ) {
            $this->_importer->doRunCreateGroupUrlTmpFiles();
            die();
        }
        if ($this->getArg('only_create_rewrites') ) {
            $this->_importer->doRunCreateRewritesFromTmpFiles();
            die();
        }

        if ( $this->_importer->needInputData() && ! $this->getArg('file') ) {
            throw new ShowHelpException();
        }
    }

    public function run() {
        if ( $this->_unserialize ) {
            $data = file_get_contents($this->getSerializeFileName());
            $this->_importer->unserializeAndRun($data, $this->_overridePackageType);
            return;
        }
        $data = null;
        if ( $this->_importer->needInputData() || $this->_dumpFile || $this->_dumpStruct || $this->_serialize ) {
            if ( ! $this->_importFile || ! file_exists($this->_importFile)  ) {
                echo 'Invalid file name "'.$this->_importFile.'".';
            }
            $data = file_get_contents($this->_importFile);
        }
        if ( $this->_dumpFile ) {
            $this->_importer->dump($data);
        } else if ( $this->_dumpStruct ) {
            $this->_importer->dumpStructure($data);
        } else if ( $this->_serialize ) {
            $res = $this->_importer->serializeData($data);
            file_put_contents($this->getSerializeFileName(),$res);
        } else {
            if ($this->_sku ) {
                $this->_importer->setDoOnlyThatSku($this->_sku);
            }
            $this->_importer->setSemaphoreFileName('/tmp/protoimporter_semaphore_cli');
            if ( $data ) {
                $this->_importer->dump2file($data);
            }
            $this->_importer->run($data,$this->_overridePackageType);
        }
    }
    
    private function getSerializeFileName () {
        $tmpDir = 'C:\\tmp';
        if ( ! is_dir($tmpDir) ) {
            $tmpDir = sys_get_temp_dir();
        }
        $res = $tmpDir . '/ProtoFileProductImport.ser';
        return $res;
    }

    public function usageHelp() {
        return self::getHelpText();
    }

    public static function getHelpText () {
        return <<<USAGE
   
Usage:  php -f ProtoFileProductImport.php [options]

  --file <file>                   The catalog-protobuf to import. Mandatory for most cases.
  --override_package_type <type>  (full|part|last)
  --sku <sku>                     handles only article <sku> and all references from article <sku> to any group
  --dump                          dumps a text representation of the given file to stdout
  --dump_struct                   dumps a text representation of the structure in the given file to stdout
  
  --no_groups                     disables processing of the groups 
  --no_articles                   disables processing of the articles
  --no_relations                  disables processing of the article-group-relations
  --no_reindex                    disables the reindexing of the catalog data
  --no_solr                       disables the solr export
  --no_megamenu                   disables the update of the megamenu ts
  --no_cachewarmup                disables warmup of product cache
  
  --only_groups                   only do the processing of the groups 
  --only_create_saved_group_urls  only create the temporary files for old group urls
  --only_articles                 only do the processing of the articles
  --only_relations                only do the processing of the article-group-relations
  --only_create_rewrites          only apply redirects from the already available temporaray files
  --only_reindex                  only do the reindexing of the catalog data
  --only_solr                     only do the solr export
  --only_megamenu                 only do update megamenu timestamp in shop and typo3 
  --only_cachewarmup              only warmup product cache

  --finalize                      build structure, references, run reindexes, solr export, touch megamenu ts

  --help                          this help
  
USAGE;
        /*
         * hidden because not needed and long time not used - still working???
  --serialize                     serializes the given file into temp dir (no further action)
  --unserializeAndRun             unserializes previous serialized data and runs the import with
         */
    }

}

try {
    $shell = new Schracklive_Shell_ProtoFileProductImport();
    $shell->run();
} catch ( ShowHelpException $ex ) {
    echo Schracklive_Shell_ProtoFileProductImport::getHelpText();
}

?>
