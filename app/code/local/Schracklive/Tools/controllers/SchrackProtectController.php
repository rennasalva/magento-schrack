<?php

require_once "CommonToolsController.php";

class Schracklive_Tools_SchrackProtectController extends Schracklive_Tools_CommonToolsController {

    public function indexAction () {
        if ( Mage::getStoreConfig('schrack/customertools/enable_lightning_protection_calculator') != '1' ) {
            $this->norouteAction();
            return;
        }
        $this->loadLayout();
        $this->constructBreadCrumbs('Lightning Protection Calculator');
        $this->renderLayout();
    }

}
