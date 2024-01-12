<?php
namespace Anowave\Package\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
abstract class Base extends \Magento\Framework\App\Helper\AbstractHelper
    {
        /** * Key bits * * @var int */
        private $bits = 3;

        /**
         * Package name
         * @var string
         */
        protected $package = '';

        /**
         * Config path
         * @var string
         */
        protected $config = '';

        /**
         * Context
         * @var \Magento\Framework\App\Helper\Context
         */
        protected $_context = null;

        /**
         * Errors array
         * @var array
         */
        private $errors = array();

        public function __construct(\Magento\Framework\App\Helper\Context $context)
        {
            $this->_context = $context;
        }

        /**
         * Check if customer is ligitimate to use the extension
         * @return boolean
         */
        final public function legit() {
            if (!extension_loaded('openssl')) {
                $this->errors[] = 'Extension requires OpenSSL.';
                return false;
            }
            $key = (array) explode(chr(58), (string) $this->decrypt($this->_context->getScopeConfig()->getValue($this->config)));

            /**
             * Check if license key configuration is available
             */
            if (!$this->config) {
                $this->errors[] = 'Invalid license key configuration'; return false;
            }

            /**
             * Check if package is available
             */
            if (!$this->package) {
                $this->errors[] = 'Invalid license key package'; return false;
            }

            /**
             * Key must contain 3 nodes
             */
            if ($this->bits !== count($key))
            {
                /** * License key is invalid */
                $this->errors[] = 'Invalid license key.';
                return false;
            }

            /**
             * Check if license key is present. If so, allow localhost always.
             */
            if ('localhost' === $_SERVER['HTTP_HOST'])
            {
                return true;
            }

            /**
             * Package and extension should match
             */
            if (strtolower($_SERVER['HTTP_HOST']) === strtolower($key[1]))
            {
                if (strtoupper($this->package) === strtoupper($key[2]))
                {
                    return true;
                }
                else
                {
                    $this->errors[] = 'The provided license key is invalid for this package';
                }
            }
            else
            {
                $this->errors[] = 'The provided license key is invalid for domain: ' . $_SERVER['HTTP_HOST'];
            }
            return false;
            return true;
        }
        /**
         * Prevent extension from generating output
         * @param string $content * @return NULL
         */
        final public function filter($content = '')
        {
            if (!$this->legit())
            {
                if (is_array($content))
                { return array();
                }
                if (is_numeric($content)) {
                    return 0;
                }
                if (is_string($content)) {
                    return ''; }
            } return $content;
        }

        /**
         * Extend content
         * @param mixed $content
         */
        final public function extend($content) {
            return $this->filter($content);
        }

        /**
         * Augment content
         * @param mixed $content
         */
        final public function augment($content) {
            return $this->filter($content);
        }
        final public function license() {
            if ($this->legit()) {
                return $this->_context->getScopeConfig()->getValue($this->config);
            } return null;
        }
        final public function notify(\Magento\Framework\Message\ManagerInterface $messanger)
        {
            if (!$this->legit())
            {
                foreach ($this->errors as $error) { $messanger->addError($error); }
            }
            return true; }

        /**
         * Decrypt key using password
         * @param unknown $string
         * @param string $password
         * @return string|NULL
         */

        private function decrypt($string)
        {
            if (extension_loaded('openssl'))
            {
                return openssl_decrypt($string, 'aes-128-cbc', openssl_decrypt('tfMyW8UoiI1or4W0q2teCG5dRuJ1MqqpGnYYYSp0dJQSykFOh1LMvqPCoG1E7Om6', 'aes-128-cbc', 'anowave'));
            } return null;
        }
    }