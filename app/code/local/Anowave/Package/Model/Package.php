<?php
/**
 * Anowave Package 
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Package
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
 
class Anowave_Package_Model_Package
{
	/**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
    	$options = array();
    	
    	foreach (array_keys((array)Mage::getConfig()->getNode('modules')->children()) as $module)
    	{
    		if (0 === strpos($module,'Anowave'))
    		{
    			$options[] = array
    			(
    				'value' => $module,
    				'label' => $module
    			);
    		}
    	}
    	
        return $options;
    }
}