<?php

class Schracklive_Tools_IndexController extends Mage_Core_Controller_Front_Action {

    public function indexAction () {
        if ( Mage::getStoreConfig('schrack/customertools/show_tools_overview_page') != '1' ) {
            $this->norouteAction();
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

}