<?php
require_once 'shell.php';

class Schracklive_Shell_DeleteCategory extends Schracklive_Shell {

    public function run() {
        $stsID = $this->getArg('stsID');
        if ( ! $stsID || $stsID < '00' ) {
            die($this->usageHelp());
        }
        $collection = Mage::getModel("catalog/category")->getCollection();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('schrack_group_id',$stsID);
        if ( count($collection) < 1 ) {
            echo 'stsID "' . $stsID . '" not found.' . PHP_EOL;
        } else {
            $category = $collection->getFirstItem();
            echo 'deleting now category ' . $category->getSchrackGroupId() . '    ' . $category->getName() . PHP_EOL;
            $category->delete();
        }
        die('done.');
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f DeleteCategory.php --stsID <id>

USAGE;
    }
}

$shell = new Schracklive_Shell_DeleteCategory();
$shell->run();

