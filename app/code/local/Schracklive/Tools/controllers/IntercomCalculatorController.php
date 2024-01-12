<?php

require_once "CommonToolsController.php";

class Schracklive_Tools_IntercomCalculatorController extends Schracklive_Tools_CommonToolsController {

    public function indexAction () {
        if ( Mage::getStoreConfig('schrack/customertools/enable_intercom_calculator') != '1' ) {
            $this->norouteAction();
            return;
        }
        $this->loadLayout();
        $this->constructBreadCrumbs('Intercom Calculator');
        $this->renderLayout();
    }

}
