<?php

require_once 'shell.php';

class Schracklive_Shell_Test extends Schracklive_Shell {

	protected function clean() {
		Mage::app()->cleanCache();
	}

	protected function flush() {
		Mage::app()->getCacheInstance()->flush();
        echo 'MAGENTO-Status : MAGENTO Cache flushed successfully' . "\n";
	}

	protected function info() {
		$cache = Mage::app()->getCache();
		$ids = $cache->getIds();


		try {
			foreach ($ids as $id) {
				echo 'Id: ', $id, chr(10);
				$data = $cache->getMetadatas($id);
				echo '* Tags: ', join(' ', $data['tags']), chr(10);
				echo '* Mod. Time: ', $data['mtime'], chr(10);
				echo '* Expire Time: ', $data['expire'], chr(10);
			}
			echo 'Filling: ', $cache->getFillingPercentage(), '%', chr(10);
		} catch (Exception $e) {
			echo 'Ids: ', join(' ', $ids), chr(10);
			echo 'Tags: ', join(' ', $cache->getTags()), chr(10);
		}

		echo 'Temp. Dir: ', $cache->getBackend()->getTmpDir(), chr(10);
	}

    protected function redis() {
        $configOptions = Mage::app()->getConfig()->getNode('global/amfpc/cache/backend');

        if ($configOptions) {
            $configOptions = $configOptions->asArray();
        } else {
            $configOptions = array();
            echo 'REDIS-Status : REDIS Cache DEACTIVATED';
        }

        if ($configOptions && $configOptions == 'Cm_Cache_Backend_Redis') {
            echo 'REDIS-Status : ACTIVE >>>>> Flushing REDIS Cache' . "\n";
            exec('redis-cli -s /var/run/redis/redis_' . str_replace('com', 'co', strtolower(Mage::getStoreConfig('schrack/general/country'))) . '.sock flushall');
            echo 'REDIS-Status : Cache flushed successfully' . "\n";
        }
    }

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f cache.php -- [options]

  info                          Show cache info
  clean                         Clean Magento cache
  config                        Removes Config Cache (Core Config Data, local.xml, etc.)
  translation                   Removes Translations from Cache (CSV, etc.)
  flush                         Flush Magento PHP-Cache (Magrento Configurations, PHP-Classes, PHP-Objects, etc.) and also REDIS Cache (Frontend Template Cache + Amasty FPC)
  redis                         Flush only REDIS Cache (Frontend Template Cache + Amasty FPC)
  help                          This help

USAGE;
	}

	public function run() {
		if ($this->getArg('info')) {
			$this->info();
		} elseif ($this->getArg('clean')) {
			$this->clean();
		} elseif ($this->getArg('config')) {
            Mage::app()->removeCache('config');
            Mage::app()->removeCache('config_global');
            Mage::app()->removeCache('config_api');
            Mage::app()->removeCache('config_api2');
		} elseif ($this->getArg('flush')) {
            $this->redis();
			$this->flush();
        } elseif ($this->getArg('redis_flush')) { // Temporarely used until triggered in Shell-Script everywhere
            $this->redis();
            $this->flush();
        } elseif ($this->getArg('redis')) {
            $this->redis();
        } elseif ($this->getArg('translation')) {
            Mage::app()->removeCache('translate');
        } else {
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklive_Shell_Test();
$shell->run();
