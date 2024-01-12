<?php

require_once 'ProtoQueueImportBase.php';

class Schracklive_Shell_ProtoQueuePromotionImport extends Schracklive_Shell_ProtoQueueImportBase {
    const STOMP_URL_CFG_PATH       = 'schrack/promotions/stomp_url';
    const STOMP_IN_QUEUE_CFG_PATH  = 'schrack/promotions/message_queue_inbound';

    /* @var $_importer Schracklive_Promotions_Model_Protoimport */
    private $_importer;
    private $_maxMessageCount = 1;
    private $_fakeKabNew;

    public function __construct () {
        parent::__construct();

        if ($this->getArg('help')) {
            die($this->usageHelp());
        }

        $this->aquireSemaphore();

        $x = $this->getArg('max_messages');
        if ( $x ) {
            $this->_maxMessageCount = intval($x);
        }

        // TODO: remove me ASAP!
        $x = $this->getArg('fake_kab_new');
        if ( $x ) {
            $this->_fakeKabNew = true;
        }
    }

    public function __destruct () {
        $this->releaseSemaphore();
        // parent::__destruct();
    }

    protected function getPidFileNameBase () {
        return 'ProtoQueuePromotions_';
    }

    protected function getInQueueCoreConfigPath () {
        return self::STOMP_IN_QUEUE_CFG_PATH;
    }

    protected function getUrlCoreConfigPath () {
        return self::STOMP_URL_CFG_PATH;
    }

    public function run () {
        if ( $this->_fakeKabNew ) {
            $this->fakeKabNew();
            echo "done.\n";
            return;
        }
        $stompClient = $this->_stompHelper->createAndSubscribeStompClientFromConfigPaths($this->getUrlCoreConfigPath(),
            $this->getInQueueCoreConfigPath());

        echo "Start Polling Procedure (reading all messages in one step)...\n";
        Mage::log('Start Polling Procedure (reading all messages in one step)...', null, '');
        $msgCount = 0;
        while ( $stompClient->hasFrame() && ($this->_maxMessageCount < 0 || $msgCount < $this->_maxMessageCount) ) {
            $msg = $stompClient->readFrame();
            if ( !$msg ) {
                echo 'No more promotion messages in queue' . PHP_EOL;
                break;
            }
            echo 'processing message ' . ++$msgCount . '/' . $this->_maxMessageCount . PHP_EOL;
            try {
                $msgBU = strtoupper($msg->headers['businessunit']);
                $shopCountry = strtoupper($this->getCountryId());
                if ( $msgBU != $shopCountry ) {
                    Mage::log("Wrong business unit ' . $msgBU . ' for shop ' . $shopCountry . ' got!", null, 'promotion_import.err.log');
                    throw new Exception("Wrong business unit '$msgBU' for shop '$shopCountry' got!");
                }
                $class = $msg->headers["protobuf_class"];
                if ( $class == 'com.schrack.queue.protobuf.SptPromotionToShop.Message' ) {
                    $this->_importer = Mage::getModel('promotions/protoimport');
                } else if ( $class == 'com.schrack.queue.protobuf.SptDeletePromotionToShop.Message' ) {
                    $this->_importer = Mage::getModel('promotions/protoimportDelete');
                } else {
                    Mage::log("Message with unexpected protobuf class '$class' got!", null, 'promotion_import.err.log');
                    throw new Exception("Message with unexpected protobuf class '$class' got!");
                }
                $this->_importer->dump2file($msg->body, $msg->headers);
                $this->_importer->run($msg->body, $msg->headers['Origin_Timestamp']);
            } catch ( Schracklive_SchrackCatalog_Model_Protoimport_DuplicateMessageException $dupMsgEx ) {
                Mage::log("ignoring duplicate message -> " . $msg->headers['Origin_Timestamp'], null, 'promotion_import.err.log');
                self::log("ignoring duplicate message");
            } catch ( Exception $ex ) {
                echo "exception caught: " . $ex->getMessage() . " - writing to error queue, see also exception log (promotion_import.err.log)\n";
                $headers = $msg->headers;
                $headers['ErrorMessage'] = $ex->getMessage();
                $msgId = $headers['message-id'];
                Mage::log(' --- Message-ID = ' . $msgId, null, 'promotion_import.err.log');
                Mage::log($ex, null, 'promotion_import.err.log');
                unset($headers['message-id']);
                unset($headers['destination']);
                unset($headers['content-length']);
                $errorQueue = Mage::getStoreConfig('schrack/promotions/message_queue_error');
                $targetQueue = $this->getQueuePathForName($errorQueue);
                $res = $stompClient->send($targetQueue, $msg->body, $headers);
                if ( !$res ) {
                    $error = $stompClient->error();
                    $error = $error ? $error : '(unknown error)';
                    unset($stompClient);
                    Mage::log('MQ message sending failed: ' . $error, null, 'promotion_import.err.log');
                    throw new Exception('MQ message sending failed: ' . $error);
                }
            }
            $stompClient->ack($msg);
            unset($msg);
            sleep(1);
        }

    }

    // TODO: remove me ASAP!
    private function fakeKabNew () {
        /* @var $writeConnection Magento_Db_Adapter_Pdo_Mysql */
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        /* @var $readConnection Magento_Db_Adapter_Pdo_Mysql */
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = "SELECT entity_id FROM catalog_product_entity";
        $dbRes = $readConnection->fetchCol($sql);
        $productMap = array();
        foreach ( $dbRes as $id ) {
            $productMap[$id] = true;
        }

        $writeConnection->beginTransaction();
        try {
            $sql = "SELECT entity_id FROM schrack_promotion WHERE type = 'KAB'";
            $dbRes = $readConnection->fetchCol($sql);
            foreach ( $dbRes as $promotionID ) {
                $sql = "DELETE FROM schrack_promotion_product WHERE promotion_id = ?";
                $writeConnection->query($sql,$promotionID);
                $sql = " SELECT DISTINCT product_id FROM schrack_promotion_account_product spap"
                     . " JOIN schrack_promotion_account spa ON spa.entity_id = spap.promotion_account_id"
                     . " WHERE spa.promotion_id = ? ORDER BY spap.`order`";
                $dbRes2 = $readConnection->fetchCol($sql,$promotionID);
                $i = 0;
                foreach ( $dbRes2 as $productID ) {
                    if ( ! $productMap[$productID] ) {
                        continue;
                    }
                    $sql = "INSERT INTO schrack_promotion_product (promotion_id, product_id, `order`) VALUES(?,?,?)";
                    $writeConnection->query($sql,array($promotionID,$productID,++$i));
                }
            }
            $writeConnection->commit();
        } catch ( Exception $ex ) {
            $writeConnection->rollback();
            Mage::log('Write DB-Operation Failed (ROLLBACK Executed)', null, 'promotion_import.err.log');
            Mage::log($ex, null, 'promotion_import.err.log');
            throw $ex;
        }
    }


    public function usageHelp() {
        return <<<USAGE

Usage:  php -f ProtoQueuePromotionsImport.php [--max_messages <cnt>]



USAGE;
    }
}

(new Schracklive_Shell_ProtoQueuePromotionImport())->run();
