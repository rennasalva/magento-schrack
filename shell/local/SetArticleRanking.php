<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_SetArticleRanking extends Mage_Shell_Abstract {

    private $sourceFile = null;
    private $readConnection, $writeConnection;

    function __construct () {
        parent::__construct();
        $this->sourceFile = $this->getArg('source_file');
		$this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

	public function run() {
        if ( ! $this->sourceFile || ! file_exists($this->sourceFile) ) {
            echo "Source file name invalid or not found" . PHP_EOL;
            die($this->usageHelp());
        }

        $data = file_get_contents($this->sourceFile);
        $data = explode("\n",$data);
        $len = count($data);
        $cnt = 0;
        foreach ( $data as $row ) {
            $sku = strtok($row,'" ');
            $rank = strtok('" ');
            $sql = "UPDATE catalog_product_entity SET schrack_wws_ranking = ? WHERE sku = ?";
            echo '.';
            $this->writeConnection->query($sql,array($rank,$sku));
            if ( ++$cnt % 1000 == 0 ) {
                echo PHP_EOL . $cnt . "/" . $len . PHP_EOL;
            }
        }

		echo PHP_EOL . 'done.' . PHP_EOL;
	}

    public function usageHelp ()
    {
        return <<<USAGE

       php SetArticleRanking.php --source_file <File>



USAGE;
    }
}

$shell = new Schracklive_Shell_SetArticleRanking();
$shell->run();
