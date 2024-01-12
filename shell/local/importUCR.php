<?php

/**
 * User: e.ayvere
 * Date: 06.08.12
 * Time: 11:11
 */

require_once 'shell.php';

class Schracklive_Shell_ImportUCR extends Schracklive_Shell {

    /**
     * Run Import UCR
     */
    public function run() {

        $file = ( parent::_getParametrizedArg('file') == '' ) ? parent::_getParametrizedArg('f') : parent::_getParametrizedArg('file');
        $mode = ( parent::_getParametrizedArg('mode') == '' ) ? parent::_getParametrizedArg('m') : parent::_getParametrizedArg('mode');
        $debug = ( in_array(parent::_getParametrizedArg('debug'), array( 'on', 'true' )) ) ? true : false;

        $mode = strtolower($mode);
        $mode = str_replace('related',   'r', $mode);
        $mode = str_replace('upsell',    'u', $mode);
        $mode = str_replace('crosssell', 'c', $mode);

        if( !is_null($mode) && $mode != '' && in_array( $mode, array( 'r', 'u', 'c' )) ) {

            if( !is_null($file) && $file != '' && file_exists( $file )) {

                $data = array();
                $workData = array();

                $handler = fopen( $file, 'r' );
                while ( ($data = fgetcsv( $handler )) !== FALSE ) {
                    foreach ( $data as $dataItem ) {
                        $workData = explode(';',$dataItem);

                        $initial = true;
                        $position = 1;
                        $product = null;
                        $linkData = array();

                        foreach($workData as $workDataItem) {
                            if( $initial ) {
                                if( $debug ) {
                                    $this->doDebugging( "-------------------start------------------" );
                                    $this->doDebugging( "BASE-PRODUCT-SKU: $workDataItem" );
                                    $this->doDebugging( "------------------------------------------" );
                                }
                                $base_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $workDataItem );

                                if ( $base_product ) {
                                    $product = $base_product;
                                    if( $debug ) { $this->doDebugging( "SKU: $workDataItem ID: ".$base_product->getId() ); }
                                }
                                else {
                                    if( $debug ) { $this->doDebugging( "BASE-PRODUCT-SKU: $workDataItem NOT FOUND!" ); }
                                }
                                $initial = false;
                            }
                            else {
                                if( $product ) {
                                    $product_id = Mage::getModel('catalog/product')->getIdBySku( $workDataItem );
                                    if( $debug ) { $this->doDebugging( "SKU: $workDataItem ID: $product_id" ); }

                                    if( $product_id ) {
                                        $linkData[$product_id] = array( 'position' => $position );
                                        $position++;
                                    }
                                    else {
                                        if( $debug ) { $this->doDebugging( "SKU: $workDataItem NOT FOUND!" ); }
                                    }
                                }
                            }
                        }

                        if( $debug ) { $this->doDebugging( "-------------------end--------------------" ); }

                        if( $product ) {
                            switch( $mode ) {
                                case 'r':
                                    $product->setRelatedLinkData( $linkData );
                                    $product->save();
                                    break;
                                case 'u':
                                    $product->setUpSellLinkData( $linkData );
                                    $product->save();
                                    break;
                                case 'c':
                                    $product->setCrossSellLinkData( $linkData );
                                    $product->save();
                                    break;
                                default:
                                    die($this->usageHelp());
                            }
                        }

                    }
                };

                fclose( $handler );
                echo 'done.'.PHP_EOL;
            }
            else {
                die($this->usageHelp());
            }
        }
        else {
            die($this->usageHelp());
        }
    }

    private function doDebugging( $data )
    {
        echo $data . "\n";
    }

    /**
     * Retrieve Usage Help Message
     */
    public function usageHelp()
    {
return <<<USAGE

  Usage:                       php importUCR.php [options]

  Filename of Importfile:      -f file.csv   or --file file.csv
  Import Related Products:     -m r          or --mode related
  Import Up-Sell Products:     -m u          or --mode upsell
  Import Cross-Sell Products:  -m c          or --mode crosssell
  This Help:                   -h            or --help

  Experienced Usage:

  Debugging:                   -debug on     or --debug true
  Logging:                     -- currently not available --

USAGE;
    }
}

$shell = new Schracklive_Shell_ImportUCR();
$shell->run();