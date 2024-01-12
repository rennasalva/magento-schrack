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
class Anowave_Ec_Model_Config
{
	public function notify()
	{
		$api = Mage::getModel('ec/api');
		
		/**
		 * Operation log
		 */
		$log = array();
		
		if ($_POST && isset($_POST['args']))
		{
			foreach (@$_POST['args'] as $entry)
			{
				$log = array_merge($log, $api->create($entry));
			}
		}
		
		if (!$log && isset($_POST['args']))
		{
			$log[] = Mage::helper('core')->__('Container configured succesfully. Please go to Google Tag Manager to preview newly created tags, variables and triggers.');
		}
		
		if ($log)
		{
			Mage::getSingleton('core/session')->addNotice
			(
				nl2br(join(PHP_EOL, $log))
			);
		}
		
		/**
		 * Check any potential issues.
		 */
		
		if ($id = (int) Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product','sku'))
		{
			if (0 == (int) Mage::getModel('catalog/resource_eav_attribute')->load($id)->getUsedInProductListing())
			{
				Mage::getSingleton('core/session')->addWarning
				(
					Mage::helper('core')->__('SKU attrubute has "Used in Product Listing" set to "No". This may result in incorrect tracking.')
				);
			}
			
		}
		
		/**
		 * Detect possible conflicts
		 */
		
		if (true)
		{
			$conflicts = array();
			
			/**
			 * Check local extensions
			 */
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/code/local'), RecursiveIteratorIterator::SELF_FIRST)  as $file)
			{
				if ($file->isFile() && false !== strpos($file->getFilename(), '.php'))
				{
					$contents = file_get_contents
					(
						$file->getPathname()
					);
						
					if (preg_match('/extends\sMage_Page_Block_Html(?!_)/i', $contents) && false === strpos($contents, 'Anowave'))
					{
						$conflicts[] = $file->getPathname();
					}
				}
			}
			

			/**
			 * Check community extensions
			 */
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/code/community'), RecursiveIteratorIterator::SELF_FIRST)  as $file)
			{
				if ($file->isFile() && false !== strpos($file->getFilename(), '.php'))
				{
					$contents = file_get_contents
					(
						$file->getPathname()
					);
			
					if (preg_match('/extends\sMage_Page_Block_Html(?!_)/i', $contents) && false === strpos($contents, 'Anowave'))
					{
						$conflicts[] = $file->getPathname();
					}
				}
			}

			if ($conflicts)
			{
				Mage::getSingleton('core/session')->addWarning
				(
					//Mage::helper('core')->__(count($conflicts) . ' possible conflict' . (1 < count($conflicts) ? 's':'') . ' detected with another extension.')
				);
			}
		}
		
	
		
		return Mage::helper('ec')->notify();
	}
}