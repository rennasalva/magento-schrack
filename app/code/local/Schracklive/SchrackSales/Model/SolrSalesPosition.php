<?php

class Schracklive_SchrackSales_Model_SolrSalesPosition extends Schracklive_SchrackSales_Model_SolrSalesEntity {
    private static $order2solrMap = array(
        'row_total'                 => 0,
        'base_row_total'            => 0,
        'row_total_incl_tax'        => 0,
        'base_row_total_incl_tax'   => 0,
        'base_tax_amount'           => 0,
        'tax_amount'                => 0,
        'schrack_surcharge'         => 0
    );

    private static $solr2orderMap = array(
        'ItemID'                => array('sku'),
        'Description'           => array('name','description'),
        'Quantity'              => array('qty_ordered','qty','qty_invoiced','qty_refunded'),
        'BackorderQuantity'     => array('qty_backordered','schrack_backorder_qty'),
        'PricingUnit'           => array('schrack_priceunit'),
        'PositionNumber'        => array('schrack_position'),
        'Price'                 => array('price'),
        'Amounts_Net'           => array('row_total','base_row_total'),
        'Amounts_Total'         => array('row_total_incl_tax','base_row_total_incl_tax'),
        'Amounts_Vat'           => array('base_tax_amount','tax_amount'),
        'Amounts_Surcharge'     => array('schrack_surcharge')
    );

    public function getExpectedAt () {
        $res = $this->getData('expected_at');
        if ( is_null($res) ) {
            $week = $this->getData('ExpectedDeliveryWeek');
            $iweek = intval($week);
            if ( $week == null || $iweek < 1 || $iweek > 53 ) {
                $res = '';
            } else {
                $year = $this->getData('ExpectedDeliveryYear');
                $res = $this->formatCalenderWeekAsDateFromTo($year,$week);
            }
            $this->setData('expected_at',$res);
        }
        return $res;
    }

    private function formatCalenderWeekAsDateFromTo ( $year, $week ) {
        // stolen from https://www.php-resource.de/forum/php-developer-forum/38929-umrechnung-kalenderwoche-in-tatsaechliches-datum.html ;-)
        $t = mktime(12,0,0,12,28,$year - 1);
        $t += $week * 604800;
        $w = date('w', $t);
        if (!$w) $w = 7;
        $t -= 86400 * ($w - 1);
        list($my,$mm, $md) = explode('-', date('Y-m-d', $t));
        list($sy,$sm, $sd) = explode('-', date('Y-m-d', $t + 345600)); // 4*24*3600
        if ( $my == $sy ) {
            return "$md.$mm.-$sd.$sm.$my";
        } else {
            return "$md.$mm.$my-$sd.$sm.$sy";
        }
    }

    protected function getStaticValueMap () {
        return self::$order2solrMap;
    }

    protected function getSolrAttributeMap () {
        return self::$solr2orderMap;
    }

}