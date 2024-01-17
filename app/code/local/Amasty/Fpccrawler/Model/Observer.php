<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpccrawler
 */


class Amasty_Fpccrawler_Model_Observer
{
    protected $_messages = array(
        'amfpccrawler_queue' => 'Explore the queue using the detailed <a href="https://amasty.com/docs/doku.php?id=magento_1%3Afull_page_cache&utm_source=extension&utm_medium=link&utm_campaign=fpc-userguide-queue#queue" target="_blank">user guide</a>.',
        'amfpccrawler_log' => '<a href="https://amasty.com/docs/doku.php?id=magento_1%3Afull_page_cache&utm_source=extension&utm_medium=link&utm_campaign=fpc-userguide-log#log" target="_blank">Learn more</a> about the log action.',
        'amfpclog' => 'Please <a href="https://amasty.com/docs/doku.php?id=magento_1%3Afull_page_cache&utm_source=extension&utm_medium=link&utm_campaign=fpc-userguide-pagesindex#pages_to_index" target="_blank">learn more</a> about page indexation.',
        'cms_block' => 'Please <a href="https://amasty.com/docs/doku.php?id=magento_1%3Afull_page_cache&utm_source=extension&utm_medium=link&utm_campaign=fpc-userguide-holepunch#how_to_hole_punch_cart_welcome_blocks" target="_blank">read the information</a> on how to prevent certain blocks from being cached (hole punch) using the Amasty Full Page Cache module.',
    );

    public function generateQueue($schedule)
    {
        $helper = Mage::helper('amfpccrawler');
        try {
            return $helper->generateQueue();
        } catch (Amasty_Fpccrawler_Helper_Lock_Exception $e) {
            ; // fix many logs at work cron
        } catch (Exception $e) {
            $schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
        }
    }

    public function processQueue($schedule)
    {
        $helper = Mage::helper('amfpccrawler');
        try {
            return $helper->processQueue();
        } catch (Amasty_Fpccrawler_Helper_Lock_Exception $e) {
            ; // fix many logs at work cron
        } catch (Exception $e) {
            $schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_ERROR);
            $schedule->setMessages($e->getMessage());
        }
    }

    public function checkCURL(Varien_Event_Observer $observer)
    {
        $params = Mage::app()->getRequest()->getParams();
        if (isset($params['section']) && $params['section'] == 'amfpccrawler') {
            // check if CURL lib exists
            if (!function_exists('curl_version')) {
                Mage::getSingleton('adminhtml/session')->addError('FPC Crawler will not work because PHP library CURL is disabled or not installed');
            }

            // echo the notice with Approx. Queue Processing Time
            $time = Mage::getResourceModel('amfpccrawler/log')->getQueueProcessingTime();
            $msg  = Mage::getModel('core/layout')
                        ->createBlock('core/template')
                        ->setProcessing($time)
                        ->setForAdminNotice(true)
                        ->setTemplate('amasty/amfpccrawler/charts/queueProcessing.phtml')
                        ->toHtml();
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);

            if (Mage::getStoreConfig('amfpccrawler/advanced/show_notifications')) {
                // check max_execution_time and warn the user
                $maxLifetime = ini_get('max_execution_time');
                $maxLifetime = $maxLifetime >= 0 ? $maxLifetime : 30;
                $processingTime = Mage::getResourceModel('amfpccrawler/log')->getQueueProcessingTime();
                if ($processingTime['cronProcessingTime'] > $maxLifetime && $maxLifetime != 0) {
                    $msg = Mage::helper('amfpccrawler')->__('Your one cron job processing time(' . $processingTime['cronProcessingTime'] . 's) is more than PHP allows(' . $maxLifetime . 's). Please, adjust your crawler settings to lower one cron job executing time!');
                    Mage::getSingleton('adminhtml/session')->addWarning($msg);
                }
                if ($processingTime['cronProcessingTime'] > 30) {
                    $msg = Mage::helper('amfpccrawler')->__('Your one cron job processing time(' . $processingTime['cronProcessingTime'] . 's) is more than PHP settings allows by default (30s). Please, check your max_execution_time PHP settings or adjust your crawler settings to lower one cron job processing time!');
                    Mage::getSingleton('adminhtml/session')->addNotice($msg);
                }
            }
        }
    }

    public function addMessage($observer)
    {
        $controllerName = Mage::app()->getRequest()->getControllerName();
        if (array_key_exists($controllerName, $this->_messages)) {
            $guideMessage = Mage::helper('amfpccrawler')->__($this->_messages[$controllerName]);
            Mage::getSingleton('adminhtml/session')->addNotice($guideMessage);
        }
    }
}
