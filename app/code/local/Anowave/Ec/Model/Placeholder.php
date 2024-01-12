<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
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
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
 
class Anowave_Ec_Model_Placeholder extends Enterprise_PageCache_Model_Container_Abstract
{
	/**
	 * Child blocks array
	 * 
	 * @var []
	 */
	public $childBlocks = array
	(
		'ec_datalayer' 			=> 'ec/datalayer.phtml',
		'ec_visitor' 			=> 'ec/visitor.phtml',
		'ec_adroll' 			=> 'ec/adroll.phtml',
		'ec_experiments'		=> 'ec/experiments.phtml',
		'ec_promotions' 		=> 'ec/promotions.phtml',
		'ec_impression' 		=> 'ec/impression.phtml',
		'ec_details' 			=> 'ec/details.phtml',
		'ec_search' 			=> 'ec/search.phtml'
	);

	public function applyWithoutApp(&$content)
	{
		return false;
	}
	
	/**
	 * Get cache
	 */
	protected function _getCacheId()
	{
		return 'ANOWAVE_HOLEPUNCH_' . microtime() . '_' . rand(0,99);
	}
	
	/**
	 * Do not save cache
	 */
	protected function _saveCache($data, $id, $tags = array(), $lifetime = null)
	{
		return false;
	}
	
	/**
	 * Get child Block
	 *
	 * @return Mage_Core_Block_Abstract
	 */
	protected function _getChildBlock($args)
	{
		return Mage::app()->getLayout()->createBlock('ec/track')->setTemplate($args->template)->setName($args->name);
	}
	
	/**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock()
    {
    	/**
    	 * Get current theme parameters 
    	 * 
    	 * @var stdClass 
    	 */
    	$theme = (object) array
    	(
    		'area' 		=> Mage::getDesign()->getArea(),
    		'package' 	=> Mage::getDesign()->getPackageName()
    	);
    	
    	/**
    	 * Change package
    	 */
    	Mage::getDesign()->setArea('frontend')->setPackageName('default');
    	
    	/**
    	 * Instantiate block 
    	 * 
    	 * @var Object
    	 */
    	$block = Mage::app()->getLayout()->createBlock('ec/track','ec_purchase');
    	
    	$block->setData('area','frontend')->setTemplate('ec/purchase.phtml');
    	
    	/**
    	 * Get cache key info arguments 
    	 * 
    	 * @var string
    	 */
    	$args = $this->_placeholder->getAttribute('args');
    	
    	if ($args)
    	{
    		$args = @unserialize($args);
    	}
    	
    	if (isset($args['order_ids']))
    	{
    		$block->setOrderIds($args['order_ids']);	
    	}
    	
    	if (isset($args['adwords']))
    	{
    		$block->setAdwords($args['adwords']);
    	}
    	
    	foreach ($this->childBlocks as $name => $template)
    	{
    		$child = Mage::app()->getLayout()->createBlock('ec/track',$name);
    		$child->setTemplate($template);

    		$block->setChild($name, $child);
    	}

    	/**
    	 * Restore package
    	 */
    	Mage::getDesign()->setArea($theme->area)->setPackageName($theme->package);
    	
    	return $block->toHtml();
    }
}