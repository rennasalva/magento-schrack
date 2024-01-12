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
 * @copyright 	Copyright (c) 2015 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_AbController extends Mage_Adminhtml_Controller_Action 
{
	public function indexAction()
	{
		$this->loadLayout()->_setActiveMenu('ec/ab')->renderLayout();
	}
	
	/**
	 * View edit form action
	 */
	public function editAction()
	{
		$this->loadLayout()->_setActiveMenu('ec/ab')->_addContent($this->getLayout()->createBlock('ec/ab_edit'))->_addLeft($this->getLayout()->createBlock('ec/ab_edit_tabs'))->renderLayout();
	}
	
	/**
	 * View new form action
	 */
	public function newAction()
	{
		$this->editAction();
	}

	public function saveAction()
	{
		if ($this->getRequest()->getPost()) 
		{
			try 
			{
				$model = Mage::getModel('ec/ab');
				
				$model->setData($this->getRequest()->getPost())->setAbId($this->getRequest()->getParam('id'))->save();
				
				if ($model->getId())
				{
					/* Update store views */
					$collection = Mage::getModel('ec/store')->getCollection()->addFieldToFilter('ab_id', $model->getId());
					
					foreach ($collection as $entity)
					{
						$entity->delete();
					}
					
					foreach ((array) $this->getRequest()->getParam('stores') as $store_id)
					{
						$store = Mage::getModel('ec/store');
						
						$store->setAbId($model->getId());
						$store->setAbStoreId((int) $store_id);
						$store->save();
					}
				}
					

				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ec')->__('A/B test was successfully saved.'));
				
				if ($this->getRequest()->getParam('back')) 
				{
				    $this->_redirect
				    (
				        '*/*/edit', array
				        (
				            'id' 	=> $model->getId(),
				            'store' => Mage::app()->getStore()
				        )
				    );
				    return;
				}
			}
			catch (Exception $e) 
			{
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				
				$this->_redirect('*/*/edit', array
				(
					'id' => $this->getRequest()->getParam('id'))
				);
				
				return;
			}
		}

		$this->_redirect('*/*/');
	}

	public function deleteAction()
	{
		if ($this->getRequest()->getParam('id') > 0) 
		{
			try 
			{
				$model = Mage::getModel('ec/ab');
				
				$model->setAbId($this->getRequest()->getParam('id'))->delete();
				
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('ec')->__('A/B test was successfully deleted'));
				
				$this->_redirect('*/*/');
			} 
			catch (Exception $e) 
			{
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				
				$this->_redirect('*/*/edit', array
				(
					'id' => $this->getRequest()->getParam('id'))
				);
			}
		}

		$this->_redirect('*/*/');
	}

	public function deleteAllAction()
	{
		$ids = $this->getRequest()->getParam('ab_id');
		
		if(!is_array($ids)) 
		{
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('ec')->__('Please select A/B test(s).'));
		} 
		else 
		{
			try 
			{
				$model = Mage::getModel('ec/ab');
				
				foreach ($ids as $id) 
				{
					$model->load($id)->delete();
				}
				
				Mage::getSingleton('adminhtml/session')->addSuccess
				(
					Mage::helper('ec')->__('Total of %d A/B test(s) were deleted.', count($ids))
				);
			} 
			catch (Exception $e) 
			{
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}
		$this->_redirect('*/*/index');
	}
	
	protected function _isAllowed()
	{
		return true;
	}
}