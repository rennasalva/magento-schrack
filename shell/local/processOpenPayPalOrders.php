<?php
    require_once 'shell.php';

    class Schracklive_Shell_GetOpenPayPalOrders extends Schracklive_Shell {
        public function __construct () {
            parent::__construct();
        }

        public function run() {
            if (intval(Mage::getStoreConfig('payment/paypal_standard/active'), 10) === 1) {
                Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php -> run::start', null, '/payment/paypal_cron.log');
                $standard = Mage::getModel('paypal/standard');
                $standard->processOpenPayPalOrders();
                Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php -> run::finished successfully', null, '/payment/paypal_cron.log');
            } else {
                Mage::log(date('Y-m-d H:i:s') . ' processOpenPayPalOrders.php -> paypal_standard: NOT ACTIVE', null, '/payment/paypal_cron.log');
            }
        }

        protected function _applyPhpVariables() {
            // do NOT apply .htaccess configurations
        }

        protected function _getParametrizedArg($name) {
            $argument = $this->getArg($name);
            if ($argument && gettype($argument) == 'string') {
                return $argument;
            }
            return false;
        }

        protected function _getArguments() {
            return $this->_args;
        }
    }

$shell = new Schracklive_Shell_GetOpenPayPalOrders();
$shell->run();