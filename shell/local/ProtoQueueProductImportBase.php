<?php

require_once 'ProtoQueueImportBase.php';

abstract class Schracklive_Shell_ProtoQueueProductImportBase extends Schracklive_Shell_ProtoQueueImportBase {
    const STOMP_URL_CFG_PATH       = 'schrack/product_import/stomp_url';
    const STOMP_IN_QUEUE_CFG_PATH  = 'schrack/product_import/message_queue_inbound';

    public function __construct () {
		parent::__construct();
    }

    protected function getPidFileNameBase () {
        return 'ProtoQueueProduct_';
    }

    protected function getInQueueCoreConfigPath () {
        return self::STOMP_IN_QUEUE_CFG_PATH;
    }

    protected function getUrlCoreConfigPath () {
        return self::STOMP_URL_CFG_PATH;
    }

}

?>