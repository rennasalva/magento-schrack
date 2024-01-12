<?php

class Schracklive_Schrack_Model_Observer
{
    /**
     * @param Varien_Event_Observer $event
     */
    public function controllerFrontInitBefore(Varien_Event_Observer $event)
    {
        require_once(Mage::getBaseDir() . DS . 'vendor' . DS . 'autoload.php');
    }
}
