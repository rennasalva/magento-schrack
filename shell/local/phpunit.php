<?php

/*

http://www.magentocommerce.com/wiki/5_-_modules_and_development/phpunit_integration_with_magento
http://oggettoweb.com/blog/unit-testing-in-magento-using-phpunit-and-xdebug/
http://www.magentocommerce.com/boards/viewthread/180746/#t232830

http://blog.ebene7.com/2010/08/24/einfache-unittests-fur-magento/

*/

if (extension_loaded('xdebug')) {
	ini_set('xdebug.show_exception_trace', 0);
}

// TODO configure path
set_include_path(dirname(dirname(dirname(__FILE__))).'/lib/PHPunit');

require_once(dirname(dirname(dirname(__FILE__))).'/app/Mage.php');
require_once('PHPUnit/Util/Filter.php');
require_once('PHPUnit/TextUI/Command.php');

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

if ($_SERVER['argc'] == 1) {
	$_SERVER['argv'][] = 'app\code\tests';	// todo configure
}

Mage::app('default');
// Mage::app('default', 'store', array('etc_dir' => 'path/to/config'));

session_start();

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');
PHPUnit_TextUI_Command::main();
