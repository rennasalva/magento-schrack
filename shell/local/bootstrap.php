<?php

require_once dirname(__FILE__).'/../../app/Mage.php';
// Start output buffering, avoiding any output before the tests execution
ob_start();
Mage::app('default');

?>