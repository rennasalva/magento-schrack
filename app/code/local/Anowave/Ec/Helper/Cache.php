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

class Anowave_Ec_Helper_Cache extends Anowave_Package_Helper_Data
{
	const CACHE_LISTING = 'ec_cache_listing';
	const CACHE_DETAILS = 'ec_cache_details';
	const CACHE_LIFETIME = 360;
	
	/**
	 * Check if cache is enabled
	 */
	public function useCache()
	{
		return Mage::app()->useCache('ec');
	}
	
	/**
	 * Load cache by tag 
	 * 
	 * @param string $tag
	 */
	public function load($id)
	{
		return Mage::app()->getCache()->load($this->generateCacheId($id), true);
	}
	
	/**
	 * Save cache
	 */
	public function save($content, $id)
	{
		Mage::app()->getCache()->save($content, $this->generateCacheId($id), array('ec'), Anowave_Ec_Helper_Cache::CACHE_LIFETIME);
		
		return $this;
	}
	
	/**
	 * Remove cache
	 */
	public function remove()
	{
		Mage::app()->getCache()->clean('all', array('ec'));
	}
	
	/**
	 * Generate unique cache id
	 * 
	 * @param string $prefix
	 */
	private function generateCacheId($prefix)
	{
		/**
		 * Push current store to make cache store specific
		 * 
		 * @var int
		 */
		$p[] = Mage::app()->getStore()->getId();
		
		/**
		 * Push request URI
		 * 
		 * @var string
		 */
		$p[] = array
		(
			$_SERVER['REQUEST_URI']
		);
		
		foreach (array($_GET, $_POST, $_FILES) as $request)
		{
			if ($request)
			{
				$p[] = $request;		
			}
		}
		
		$p = md5(serialize($p));

		/**
		 * Merge
		 */
		return "{$prefix}_{$p}";
	}
}