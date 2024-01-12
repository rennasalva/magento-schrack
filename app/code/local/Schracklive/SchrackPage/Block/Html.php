<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Schracklive_SchrackPage_Block_Html extends Mage_Page_Block_Html {
    public function __construct() {
        parent::__construct();
        $action = Mage::app()->getFrontController()->getAction();
        if ($action) {
            $this->addBodyClass($action->getFullActionName(' '));
        }
    }

	// Use de-AT, de-DE etc. instead of default de
	public function getLang() {
		if (!$this->hasData('lang')) {
			$this->setData('lang', str_replace('_','-',substr(Mage::app()->getLocale()->getLocaleCode(), 0, 5)));
		}
		return $this->getData('lang');
	}
    
    
    /**
     * Add CSS class to page body tag
     *
     * @param string $className
     * @return Mage_Page_Block_Html
     */
    public function addBodyClass($className)
    {
        $className = preg_replace('#[^a-z0-9 ]+#', '-', strtolower($className));
        $this->setBodyClass($this->getBodyClass() . ' ' . $className);
        return $this;
    }
}