<?php

require_once 'ProtoQueueProductImportBase.php';

class Schracklive_Shell_PrepareOffers extends Schracklive_Shell {

    var $csv = null;
    var $writeConnection = null;
    var $readConnection = null;
    var $yesterday = null;
    var $inOneYear = null;


    public function __construct () {
        parent::__construct();
        if ($this->getArg('help')) {
            die($this->usageHelp());
        }
        if ( ! ($this->csv = $this->getArg('orders2offers')) ) {
            die($this->usageHelp());
        }
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->yesterday = date("Y-m-d",time() - (24*60*60));
        $this->inOneYear = date("Y-m-d",time() + (365*24*60*60));
    }

    public function run () {
        $csvArray = explode(',',$this->csv);
        foreach ( $csvArray as $number2number ) {
            list($orderNumber,$offerNumber,$webSendNr) = explode('=',$number2number);
            $this->prepareOneOffer($orderNumber,$offerNumber,$webSendNr);
        }
        echo PHP_EOL . "done." . PHP_EOL;
    }

    private function prepareOneOffer ( $orderNumber, $offerNumber, $webSendNr ) {
        if ( ! $webSendNr ) {
            $webSendNr = 0;
        }
        $sql = "SELECT entity_id FROM sales_flat_order WHERE schrack_wws_order_number = '$orderNumber'";
        echo $sql . PHP_EOL;
        $orderId = $this->readConnection->fetchOne($sql);
        if ( ! $orderId ) {
            die("Invalid order number $orderNumber !");
        }
        $sql = "SELECT customer_id FROM sales_flat_order WHERE entity_id = $orderId;";
        echo $sql . PHP_EOL;
        $customerId = $this->readConnection->fetchOne($sql);
        $sql = "SELECT sku, name, schrack_position FROM sales_flat_order_item WHERE order_id = $orderId";
        $posData = $this->readConnection->fetchAll($sql);
        $sql = "SELECT schrack_wws_customer_id FROM customer_entity WHERE entity_id = $customerId;";
        echo $sql . PHP_EOL;
        $wwsCustomerId = $this->readConnection->fetchOne($sql);

        // setup order record:
        $sql = "UPDATE sales_flat_order SET schrack_wws_offer_number='$offerNumber', `schrack_wws_offer_date`='$this->yesterday', `schrack_wws_offer_valid_thru`='$this->inOneYear', `schrack_wws_offer_flag_valid`='1', `schrack_wws_web_send_no`='$webSendNr' WHERE entity_id = $orderId;";
        echo $sql . PHP_EOL;
        $this->writeConnection->query($sql);

        // add index entry for order if necessary:
        $sql = "SELECT entity_id FROM sales_flat_order_schrack_index WHERE wws_document_number = $orderNumber AND order_id = $orderId;";
        echo $sql . PHP_EOL;
        $orderNdxId = $this->readConnection->fetchOne($sql);
        if ( ! $orderNdxId ) {
            $sql = "INSERT INTO sales_flat_order_schrack_index (`wws_customer_id`, `wws_document_number`, `order_id`, `is_offer`, `is_order_confirmation`, `is_processing`, `document_date_time`) "
                 . "VALUES ($wwsCustomerId, '$orderNumber', $orderId, '0', '0', '0', '$this->yesterday');";
            echo $sql . PHP_EOL;
            $this->writeConnection->query($sql);
            $sql = "SELECT entity_id FROM sales_flat_order_schrack_index WHERE wws_document_number = $orderNumber AND order_id = $orderId;";
            echo $sql . PHP_EOL;
            $orderNdxId = $this->readConnection->fetchOne($sql);
            if ( ! $orderNdxId ) {
                die('error creating index record!');
            }
            // add positions
            $i = 1;
            foreach ( $posData as $pos ) {
                $sku = $pos['sku']; $name = $pos['name'];
                $sql = "INSERT INTO sales_flat_order_schrack_index_position (`parent_id`, `position`, `sku`, `description`) VALUES ($orderNdxId, $i, '$sku', '$name');";
                echo $sql . PHP_EOL;
                $this->writeConnection->query($sql);
            }
        }

        // add index entry for order if necessary:
        $sql = "SELECT entity_id FROM sales_flat_order_schrack_index WHERE wws_document_number = $offerNumber AND order_id = $orderId;";
        echo $sql . PHP_EOL;
        $offerNdxId = $this->readConnection->fetchOne($sql);
        if ( ! $offerNdxId ) {
            $sql = "INSERT INTO sales_flat_order_schrack_index (`wws_customer_id`, `wws_document_number`, `order_id`, `is_offer`, `is_order_confirmation`, `is_processing`, `document_date_time`) "
                . "VALUES ($wwsCustomerId, '$offerNumber', $orderId, '1', '0', '0', '$this->yesterday');";
            echo $sql . PHP_EOL;
            $this->writeConnection->query($sql);
        }
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  php -f PrepareOffers.php --orders2offers <csv>

Where <csv> has following structure: <OrderNo1>=<OfferNo1>[=<WebSendNr1>],<OrderNo2>=<OfferNo2>[=<WebSendNr2>]...

Default for <WebSendNrX> is 0.


USAGE;
    }
}

$shell = new Schracklive_Shell_PrepareOffers();
$shell->run();

?>
