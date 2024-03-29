<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpccrawler
 */


class Amasty_Fpccrawler_Helper_Data extends Mage_Core_Helper_Abstract
{
    const DEFAULT_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/44.0.2403.89 Chrome/44.0.2403.89 Safari/537.36';
    const USER_AGENT_EXTENSION = 'Amasty_Fpccrawler';

    const MAX_ROWS_BEFORE_CLEAN = '500';
    const FILE_LOCK_GENERATE = 'amfpccrawler_lock_generate.lock';
    const FILE_LOCK_PROCESS = 'amfpccrawler_lock_process.lock';

    private $_postData          = false;
    private $_cookieFilePath    = false;
    private $_cookieFileContent = '';

    private $_statusCodes = array(
        0 => 'Already cached',
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended'
    );

    private $_blockedUrls = array(
        'directory/currency/switch',
        'customer/account/loginPost',
        '__store=',
        'amfpccrawler',
        'customer/account/',
        '/logout',
    );

    /**
     * finds source with URL list for crawler bot to walk through
     *
     * @return Mage_Core_Model_Abstract
     */
    public function getQueueSource()
    {
        $source = array();
        $error = '';
        $loadLimit = Mage::getStoreConfig('amfpccrawler/queue/queue_limit');
        $sourceSelected = Mage::getStoreConfig('amfpccrawler/queue/source');

        if ($sourceSelected == 'fpc') {
            /*
             * FPC built-in tables
             */
            if (Mage::helper('core')->isModuleEnabled('Amasty_Fpc')) {
                $data = Mage::getResourceModel('amfpc/url_collection')->setOrder('rate', 'DESC')->setPageSize($loadLimit)->setCurPage(1);
                foreach ($data as &$item) {
                    $source[] = array(
                        'rate' => $item->getRate(),
                        'url'  => $item->getUrl(),
                    );
                }
            } else {
                $error = $this->__('FPC selected as source, but no FPC module installed.');
                $this->logDebugMessage('find_resource', $error);
            }
        } else if ($sourceSelected == 'file') {
            /*
             * load file with each new line = new url
             */
            $filePath = Mage::getStoreConfig('amfpccrawler/queue/queue_file_path');
            if (file_exists($filePath)) {
                $fileContent = file_get_contents($filePath);
                $source      = preg_split('/[,\s]+/', $fileContent, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($source as &$item) {
                    $item = array(
                        'rate' => 1,
                        'url'  => $item,
                    );
                }
            } else {
                $error = $this->__('File selected as source, but file does not exist with specified path: %s', $filePath);
                $this->logDebugMessage('find_resource', $error);
            }
        } else if ($sourceSelected == 'sitemap') {
            /*
             * load SiteMap XML file and parse it
             */
            $filePath = Mage::getBaseDir('base') . '/sitemap.xml';
            if (file_exists($filePath)) {
                $xml = simplexml_load_file($filePath);
                foreach ($xml->url as $url) {
                    $source[] = array(
                        'rate' => round(trim((string)$url->priority) * 100), //convert float 0.5 into percent value 50%
                        'url'  => trim((string)$url->loc),
                    );
                }
            } else {
                $error = $this->__('Sitemap selected as source, but sitemap file does not exist in the root directory: %s', $filePath);
                $this->logDebugMessage('find_resource', $error);
            }
        } else if ($sourceSelected == 'magento') {
            /*
             * fetch data from default magento URL log table
             */
            /** @var Mage_Core_Model_Resource $res */
            $res     = Mage::getSingleton('core/resource');
            $query   = "" . 'SELECT `url`, COUNT(`url_id`) as `rate` FROM ' . $res->getTableName('log_url_info') . " GROUP BY `url` ORDER BY `rate` DESC";
            $data    = $res->getConnection('core_read');
            $results = $data->fetchAll($query);
            if ($results) {
                foreach ($results as $k => &$item) {
                    $source[] = array(
                        'rate' => $item['rate'],
                        'url' => $this->getRewrittenUrl($item['url'])
                    );
                }
            }
        } else {
            $error = $this->__('Selected unsupported method as source for queue.');
            $this->logDebugMessage('find_resource', $error);
        }

        if (!$error) {
            $storeIds = array();
            if (Mage::getStoreConfig('amfpccrawler/processing/store_enabled')) {
                $storeIds = Mage::getStoreConfig('amfpccrawler/processing/store');
                $storeIds = $storeIds ? explode(',', trim($storeIds, ',')) : array();
            }

            $storeUrls = array();
            if (!empty($storeIds)) {
                $storeCollection = Mage::getResourceModel('core/store_collection')
                    ->addIdFilter($storeIds);
                foreach ($storeCollection as $store) {
                    $websiteUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                    if (!in_array($websiteUrl, $storeUrls)) {
                        $storeUrls[$store->getId()] = $websiteUrl;
                    }
                }
            }

            foreach ($source as $k => &$item) {
                if ($this->inIgnoreList($item['url']) || $this->containsIgnoredParams($item['url'])) {
                    unset($source[$k]);
                } elseif (!empty($storeUrls)) {
                    $found = false;
                    foreach ($storeUrls as $storeId => $storeUrl) {
                        if (false !== strpos($item['url'], $storeUrl)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        unset($source[$k]);
                    }
                }
            }
        }

        return array('error' => $error, 'source' => $source);
    }

    /**
     * logs debug message into special log file
     * note: it forces logging + different file for each "area"
     *
     * @param $area
     * @param $message
     */
    public function logDebugMessage($area, $message)
    {
        Mage::log($message, null, 'amfpccrawler_' . $area . '.log', true);
    }

    private function getRewrittenUrl($url)
    {
        $coreUrl = Mage::getModel('core/url_rewrite');
        $urlData = parse_url($url);
        $path    = trim($urlData['path'], '/');

        $coreUrl->load($path, 'target_path');
        $path = $coreUrl->getRequestPath();

        if (!$path) {
            $path = $url;
        } else {
            $path = Mage::getBaseUrl('web') . $path;
        }

        return $path;
    }

    /**
     * check if url is in Ignore List
     *
     * @return bool
     */
    public function inIgnoreList($path)
    {
        $ignore = Mage::getStoreConfig('amfpc/pages/ignore_list');
        $ignoreList = preg_split('|[\r\n]+|', $ignore, -1, PREG_SPLIT_NO_EMPTY);
        $ignoreList = array_unique(array_merge($this->_blockedUrls, $ignoreList));

        foreach ($ignoreList as $pattern) {
            if (preg_match("|$pattern|", $path))
                return true;
        }

        return false;
    }

    private function containsIgnoredParams($path)
    {
        if ($paramsString = Mage::getStoreConfig('amfpc/pages/ignored_params')) {
            $params = preg_split('/[,\s]+/', $paramsString, -1, PREG_SPLIT_NO_EMPTY);
            if (!empty($params)) {
                foreach ($params as $param) {
                    if (strpos($path, $param . '=') !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * deletes COOKIE file for specified customer group
     * e.g. removes auth data
     *
     * @param bool $customerGroup
     * @param string $currency
     */
    public function delAuthCookie($customerGroup, $currency)
    {
        $this->_cookieFilePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.$currency.txt";
        if ($this->_cookieFilePath) {
            if (file_exists($this->_cookieFilePath)) {
                unlink($this->_cookieFilePath);
            }
            $this->_cookieFilePath    = false;
            $this->_cookieFileContent = '';
        }
    }

    /**
     * retrieves COOKIE file
     * with specified "customer_group" customer logged in
     * and
     *
     * @param string $customerGroup
     * @param string $currency
     * @param string $currencyUrl
     * @param int    $storeId
     *
     * @return bool
     */
    public function getAuthCookie($customerGroup, $currency, $currencyUrl = '', $storeId = 0)
    {
        // convert values
        $currency               = $currency ? $currency : 'default';
        $customerGroup          = $customerGroup ? $customerGroup : 'default';
        $cookieReceived         = false;

        // set cookie path
        $this->_cookieFilePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.txt";

        // get cookie with customer group logged in
        if ($customerGroup && $customerGroup != 'default') {
            list($user, $email, $pass) = $this->getCustomerGroupCredentials($customerGroup, $storeId);

            // check if cookie already exists
            if (!file_exists($this->_cookieFilePath) || $this->cookieExpired()) {
                // empty cached cookie data and get new cookie
                $this->_cookieFileContent = '';

                //delete cookie file
                if (file_exists($this->_cookieFilePath)){
                    @unlink($this->_cookieFilePath);
                }

                // first request: just getting form_key
                list($res, $status) = $this->getUrl(Mage::getUrl('customer/account/login', array('_store' => $storeId)));

                // get form_key only if it is available to log in
                if (strpos($res, 'form_key') !== false) {
                    $form_key_start        = strpos($res, 'form_key');
                    $form_key_start        = strpos($res, 'value="', $form_key_start + 1); // find value of hidden input
                    $form_key_end          = strpos($res, '"', $form_key_start + 8); // get end of a line (including start offset of 8 symbols
                    $form_key              = substr($res, $form_key_start + 7, $form_key_end - $form_key_start - 7); // seven symbols offset is for start word
                    $this->_cookieFilePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.txt";
                    $this->_postData       = "login[username]=" . ($email) . "&login[password]=" . ($pass) . "&form_key=" . ($form_key);

                    // second request: authorize with given key
                    list($res, $status) = $this->getUrl(Mage::getUrl('customer/account/loginPost', array('_store' => $storeId)));

                    // return success
                    $this->_cookieFilePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.txt";
                    $cookieReceived        = true;
                } else {
                    $customerGroup         = 'default';
                    $this->_cookieFilePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.txt";
                    $cookieReceived        = true;
                }
            }
        }


        // get cookie with specified currency
        if ($currency && $currency != 'default') {
            $cookiePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.$currency.txt";;
            $this->_cookieFilePath = $cookiePath;
            if (file_exists($this->_cookieFilePath) && !$this->cookieExpired()) {
                $this->_cookieFilePath = $cookiePath;
            } else {
                $this->_cookieFileContent = '';
                if (file_exists($this->_cookieFilePath)) {
                    unlink($this->_cookieFilePath);
                }
                $userCookiePath = Mage::getBaseDir('tmp') . "/amfpccrawler/cookies/$customerGroup.txt";
                if (file_exists($userCookiePath)) {
                    copy($userCookiePath, $this->_cookieFilePath);
                }
                $this->getUrl($currencyUrl);
                $this->_cookieFilePath = $cookiePath;
                $cookieReceived = true;
            }
        }

        // add special FPCcrawler flag to the cookie
        if ($cookieReceived) {
            $domainData = parse_url(Mage::getBaseUrl('web'));
            $domain     = $domainData['host'];
            $cookieFlag = '.' . $domain . '	TRUE	/	FALSE	' . strtotime('+1 month') . '	amfpc_crawler	1';
            file_put_contents($this->_cookieFilePath, $cookieFlag, FILE_APPEND | LOCK_EX);
        }

        return $this->_cookieFilePath;
    }

    /**
     * generates customer credentials for specified "customer_group"
     *
     * @param $customerGroup
     * @param $storeId
     *
     * @return array
     */
    private function getCustomerGroupCredentials($customerGroup, $storeId)
    {
        // check if user exists. if not - create user for further login
        if (!$storeId) {
            $storeId = Mage::app()
                           ->getWebsite()
                           ->getDefaultGroup()
                           ->getDefaultStoreId();
        }
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $websiteId = $websiteId ? $websiteId : 1; // prevent from "0" value (because it is default Admin website store view)

        $user = 'FPC.Crawler.' . $customerGroup . '.' . $websiteId;
        $mail = $user . '@amasty.com';
        $hash = md5($customerGroup);
        $pass = substr($hash, 1, 5) . substr($hash, 9, 3);

        $userData = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($mail);
        if (!$userData->getId()) {
            $this->createUser($customerGroup, $websiteId);
        }

        return array($user, $mail, $pass);
    }

    /**
     * creating new user for specified customer group code
     *
     * @param $customerGroup
     * @param $websiteId
     *
     * @return bool
     */
    public function createUser($customerGroup, $websiteId)
    {
        $username = 'FPC.Crawler.' . $customerGroup . '.' . $websiteId;
        $email    = $username . '@amasty.com';
        $hash     = md5($customerGroup);
        $pass     = substr($hash, 1, 5) . substr($hash, 9, 3);

        $user = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($email);
        if (!$user->getId()) {
            try {
                // prepare new customer object
                $user = Mage::getModel('customer/customer');
                $user->setData(array(
                        'username'  => $username,
                        'firstname' => 'FPC',
                        'lastname'  => 'Crawler',
                        'email'     => $email,
                        'password'  => $pass,
                        'is_active' => 1
                    )
                );

                $user->setWebsiteId($websiteId);
                $user->setGroupId($customerGroup);

                // save customer
                $user->save();
            } catch (Exception $e) {
                $this->logDebugMessage('create_user', $e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * checks cookie file expiration date
     *
     * @return bool
     */
    private function cookieExpired()
    {
        if ($this->_cookieFilePath) {
            // get cookie data
            if (!$this->_cookieFileContent) {
                $this->_cookieFileContent = file_get_contents($this->_cookieFilePath);
            }
            $cookie = $this->_cookieFileContent;

            // find expired value
            $matches = array();
            $res     = preg_match_all('#[0-9]{10}#', $cookie, $matches);
            if ($res > 0) {
                $expired = min(array_values($matches[0]));
                $time    = time();
            } else {
                return true;
            }

            // if expiration date less than current (e.g. already gone and was in past) = FALSE = cookie expired
            if ($time > $expired) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * retrieve URL contents with specified parameters
     *    - group    : customer group code
     *    - store    : store ID
     *    - currency : currency code like 'USD'
     *    - mobile   : whenever mobile version must be retrieved
     *
     * @param      $url
     * @param bool $group
     * @param bool $storeId
     * @param bool $currency
     * @param bool $mobile
     * @param int  $rate
     * @param bool $logging
     *
     * @return bool
     */
    public function getUrl($url, $group = false, $storeId = false, $currency = false, $mobile = false, $rate = 0, $logging = false)
    {
        // start time point && initial data for log
        if ($logging) {
            $loadStart = time();
            $bind      = array($url, $group, $storeId, $currency, $mobile);
        }

        // check CURL lib
        if (!function_exists('curl_version')) {
            return false;
        }

        // check if any URL given
        if (!$url) {
            return false;
        } else {
            $request = curl_init();
        }

        // add store switch into GET query
        if ($storeId) {
            $store = Mage::getModel('core/store')->load($storeId)->getCode();
            $url .= (strpos($url, '?') === FALSE ? '?' : '&') . '___store=' . $store;
        }

        // get currency switch URL
        if ($currency) {
            $default = Mage::app()->getWebsite(true)->getDefaultGroup()->getDefaultStoreId();
            $url = $this->getUrlWithCurrency($url, $currency, $storeId ?: $default);
        }

        // retrieve && attach COOKIE file for customer_group logged in user
        if ($group || $currency) {
            // get cookie
            if (!$this->getAuthCookie($group, $currency, $url, $storeId)) {
                $this->logDebugMessage('auth_cookie', 'Auth cookie retrieve fail for group: ' . $group);
                curl_close($request);

                return false;
            }
        }

        // attach cookie to the request if any cookie-path given
        if ($this->_cookieFilePath) {
            curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($request, CURLOPT_COOKIESESSION, true);
            curl_setopt($request, CURLOPT_COOKIEJAR, $this->_cookieFilePath);
            curl_setopt($request, CURLOPT_COOKIEFILE, $this->_cookieFilePath);
        }

        // set default CURL params
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
       // curl_setopt($request, CURLOPT_VERBOSE, 1);
        curl_setopt($request, CURLOPT_HEADER, 1);

        // retrieve MOBILE version
        if ($mobile) {
            $userAgent = Mage::getStoreConfig('amfpccrawler/options/mobile_agent');
        }
        else {
            $userAgent = self::DEFAULT_USER_AGENT;
        }

        $userAgent .= ' ' . self::USER_AGENT_EXTENSION;

        curl_setopt($request, CURLOPT_USERAGENT, $userAgent);

        // add POST data
        if ($this->_postData) {
            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_POSTFIELDS, $this->_postData);
        }

        if (Mage::getStoreConfigFlag('amfpccrawler/advanced/http_auth')) {
            $login = trim(Mage::getStoreConfig('amfpccrawler/advanced/login'));
            $password = trim(Mage::getStoreConfig('amfpccrawler/advanced/password'));

            if ($login && $password) {
                curl_setopt($request, CURLOPT_USERPWD, $login . ":" . $password);
            }
        }

        if (Mage::getStoreConfig('amfpccrawler/advanced/certificate')) {
            curl_setopt($request, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        }

        // resolve the request
        $result = curl_exec($request);

        if (curl_error($request)) {
            Mage::log(curl_error($request), null, 'amfpccrawler_curl_error.log', true);
        }
        $status = curl_getinfo($request, CURLINFO_HTTP_CODE);

        // log results
        if ($logging) {
            /**@var Amasty_Fpccrawler_Model_Resource_Log $log */
            $log  = Mage::getResourceModel('amfpccrawler/log');
            $load = time() - $loadStart;
            list($url, $group, $storeId, $currency, $mobile) = $bind;
            $log->addToLog($url, $group, $storeId, $currency, $mobile, $rate, $status, $load);
        }

        // log result for debug
        if (Mage::getStoreConfig('amfpccrawler/advanced/debug')) {
            $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
            $header      = substr($result, 0, $header_size);
            $this->logDebugMessage(
                'debug', 'Getting URL "' . $url . '"' . "\r\n"
                         . '  - status: ' . $status . "\r\n"
                         . '  - content size: ' . strlen($result) . "\r\n"
                         . '  - cookie path: ' . $this->_cookieFilePath . "\r\n"
                         . '  - headers: ' . "\r\n" . $header . str_repeat("\r\n", 5)
            );
            $this->logDebugMessage('debug_url_content', 'Getting URL "' . $url . '"' . "\r\n" . $result . str_repeat("\r\n", 10));
        }

        // clean data
        curl_close($request);
        $this->_postData          = false;
        $this->_cookieFilePath    = '';
        $this->_cookieFileContent = '';

        // send result
        $acceptedStatus = Mage::getStoreConfig('amfpccrawler/options/accepted_status');
        $acceptedStatus = explode(',', $acceptedStatus);
        if (in_array($status, $acceptedStatus)) {
            return array($result, $status);
        } else {
            $this->logDebugMessage('get_url', 'Getting URL "' . $url . '" failed with status: ' . $status);
            return false;
        }

    }

    /**
     * generates special URL to switch currency
     * IMPORTANT: contains one redirect, so CURL request must follow redirects
     *
     * @param $url
     * @param $currency
     * @param $storeId
     *
     * @return string
     */
    private function getUrlWithCurrency($url, $currency, $storeId)
    {
        $url = Mage::getUrl('directory/currency/switch', array(
                'currency'                                                => $currency,
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core')->urlEncode($url),
                '_store'                                                  => $storeId
            )
        );

        return $url;
    }

    public function getStatusCodeDescription($code)
    {
        if (isset($this->_statusCodes[$code])) {
            $res = $this->_statusCodes[$code];
        } else {
            $res = 'Unknown code status';
        }

        return $res;
    }

    /**
     * recursive search of a substring from array of patterns given
     *
     * @param $string string  haystack
     * @param $array  array   patterns to search
     *
     * @return bool
     */
    private function strpos_array($string, $array)
    {
        foreach ($array as $item) {
            if (strpos($string, $item) !== false) {
                return true;
            }
        }

        return false;
    }

    public function generateQueue()
    {
        // check if crawler enabled
        if (!Mage::getStoreConfig('amfpccrawler/general/enabled')) {
            throw new Exception('The Amasty FPC Crawler is not enabled. Please, enable the Amasty FPC Crawler module.');
        }

        // check if another cron is still running
        $lockFile = Mage::getBaseDir('tmp') . DS . self::FILE_LOCK_GENERATE;
        $fp       = fopen($lockFile, 'w');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            throw new Amasty_Fpccrawler_Helper_Lock_Exception('Another lock detected');// another lock detected
        }

        /** @var Amasty_Fpccrawler_Model_Resource_Queue $res */
        $cnt = 0;
        $queueSource = $this->getQueueSource();
        $resource = Mage::getResourceModel('amfpccrawler/queue');

        if ($queueSource['error']) {
            throw new Exception($queueSource['error']);
        }

        if ($queueSource['source']) {
            foreach ($queueSource['source'] as $item) {
                // add only real URLs
                if (strlen($item['url']) > 5) {
                    $bind = array($item['url'], $item['rate'], $item['rate']);

                    $res = $resource->addToQueue($bind);
                    if (!$res) {
                        $this->logDebugMessage('queue_add', 'Failed to add queue: ' . $item['url']);
                    }
                } else {
                    $this->logDebugMessage('queue_add', 'Url is too short: ' . $item['url']);
                }
                // empty queue every X rows inserted
                if ($cnt++ > self::MAX_ROWS_BEFORE_CLEAN) {
                    $cnt = 0;
                    $resource->cleanQueue();
                }
            }
            $resource->cleanQueue();
        } else {
            $message = 'Crawler source is empty. ';
            $source = Mage::getStoreConfig('amfpccrawler/queue/source');
            switch ($source) {
                case 'fpc':
                    $message .= 'The FPC built-in table is selected as source, ';
                    if (!Mage::getStoreConfig('amfpc/stats/visits')) {
                        $message .= 'but the `Collect Page Visit Statistics` setting is disabled. Please, enable this setting here: Backend > System > Configuration > Amasty Extensions > Full Page Cache > Statistics.';
                    } else {
                        $tableName = Mage::getSingleton('core/resource')->getTableName('amfpc/url');
                        $message .= 'but the database table is empty: ' . $tableName . '; Please, browse the store pages to add the new records to the database.';
                    }
                    break;
                case 'file':
                    $message .= 'The file selected as source, ';
                    $filePath = Mage::getStoreConfig('amfpccrawler/queue/queue_file_path');
                    if (file_exists($filePath)) {
                        $message .= 'but specified file is empty: ' . $filePath . '; Please, add at least one link to the file.';
                    } else {
                        $message .= 'but the file does not exist on the specified path: ' . $filePath . '; Please, create the file.';
                    }
                    break;
                case 'sitemap':
                    $message .= 'The sitemap is selected as source, ';
                    $filePath = Mage::getBaseDir('base') . '/sitemap.xml';
                    if (file_exists($filePath)) {
                        $message .= 'but the sitemap is empty: ' . $filePath . '; Please fill in the sitemap file.';
                    } else {
                        $message .= 'but the sitemap does not exist with path: ' . $filePath . '; Please, create the file.';
                    }
                    break;
                case 'magento':
                    $message .= 'The Magento is selected as source, ';
                    if (!Mage::helper('core')->isModuleEnabled('Mage_Log')) {
                        $message .= 'but the Mage_Log module is disabled. Please, enable the Mage_Log module.';
                    } else {
                        $tableName = Mage::getSingleton('core/resource')->getTableName('log_url_info');
                        $message .= 'but the database table is empty: ' . $tableName . '; Please, browse the store pages to add the new records to the database.';
                    }
                    break;
            }
            throw new Exception($this->__($message));
        }

        // remove the lock
        fclose($fp);

        return true;
    }

    public function processQueue()
    {
        // check if crawler enabled
        if (!Mage::getStoreConfig('amfpccrawler/general/enabled')) {
            throw new Exception('The Amasty FPC Crawler is not enabled. Please, enable the Amasty FPC Crawler module.');
        }

        // check if another cron is still running
        $lockFile = Mage::getBaseDir('tmp') . DS . self::FILE_LOCK_PROCESS;
        $fp       = fopen($lockFile, 'w');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            throw new Amasty_Fpccrawler_Helper_Lock_Exception('Another lock detected');// another lock detected
        }

        /**@var Amasty_Fpccrawler_Model_Resource_Queue_Collection $source */
        /**@var Amasty_Fpccrawler_Model_Resource_Log $log */
        $logNum    = 0;
        $sourceNum = 0;
        $linksDone = 0;
        $log       = Mage::getResourceModel('amfpccrawler/log');
        $limit     = Mage::getStoreConfig('amfpccrawler/queue/process_limit');
        $source    = Mage::getResourceModel('amfpccrawler/queue_collection')->setOrder('rate', 'DESC');
        $source    = array_values($source->getItems());//remove associative array keys
        $log->cleanLog();

        // get config data
        if (Mage::getStoreConfig('amfpccrawler/processing/store_enabled')) {
            $stores = Mage::getStoreConfig('amfpccrawler/processing/store');
        } else {
            $stores = array();
        }
        if (Mage::getStoreConfig('amfpccrawler/processing/currency_enabled')) {
            $currencies = Mage::getStoreConfig('amfpccrawler/processing/currency');
        } else {
            $currencies = array();
        }
        if (Mage::getStoreConfig('amfpccrawler/processing/customer_group_enabled')) {
            $customerGroups = Mage::getStoreConfig('amfpccrawler/processing/customer_group');
        } else {
            $customerGroups = array();
        }
        $mobiles = Mage::getStoreConfig('amfpccrawler/processing/mobile') ? array(true) : array();

        // reverse string-stored values into arrays
        $stores         = $stores ? explode(',', trim($stores, ',')) : array();
        $currencies     = $currencies ? explode(',', trim($currencies, ',')) : array();
        $customerGroups = $customerGroups ? explode(',', trim($customerGroups, ',')) : array();

        // filter parameters
        if (is_array($customerGroups) && isset($customerGroups[0]) && $customerGroups[0] == '0') {
            unset($customerGroups[0]);
        }

        // add false items into arrays
        array_unshift($stores, false);
        array_unshift($mobiles, false);
        array_unshift($currencies, false);
        array_unshift($customerGroups, false);

        // loop through each link and receive it
        //     $limit --> number of real requests that can be done for one
        //     cron job running without counting any already cached pages
        while ($linksDone <= $limit && isset($source[$sourceNum])) {
            $link = $source[$sourceNum++];
            // loop through each store for all CUSTOMER GROUPS
            foreach ($customerGroups as $customerGroup) {
                // loop through each STORE
                foreach ($stores as $store) {
                    // get MOBILE or normal versions of a page
                    foreach ($currencies as $currency) {
                        // get MOBILE or normal versions of a page
                        foreach ($mobiles as $mobile) {
                            // check if we need to update this page cache
                            /**@var Varien_Object $data */
                            $data = new Varien_Object();
                            $data->setData('customerGroup', $customerGroup);
                            $data->setData('url', ($link->getUrl()));
                            $data->setData('currency', $currency);
                            $data->setData('mobile', $mobile);
                            $data->setData('store', $store);
                            $data->setData('hasCache', false);
                            Mage::dispatchEvent('amfpccrawler_process_link', array('data' => $data));

                            // proceed update if needed
                            if ($data->getData('hasCache') == false) {
                                // get URL
                                list($res, $status) = $this->getUrl($link->getUrl(), $customerGroup, $store, $currency, $mobile, $link->getRate(), true);

                                // check result
                                if (!$status) {
                                    $this->logDebugMessage('queue_process', 'Failed to request: ' . $link->getUrl());
                                }

                                // clear log every X rows inserted
                                $linksDone++;
                                if ($logNum++ > self::MAX_ROWS_BEFORE_CLEAN) {
                                    $logNum = 0;
                                    $log->cleanLog();
                                }
                            } else {
                                /**@var Amasty_Fpccrawler_Model_Resource_Log $log */
                                $log = Mage::getResourceModel('amfpccrawler/log');
                                $log->addToLog($link->getUrl(), $customerGroup, $store, $currency, $mobile, $link->getRate(), 0, 0);
                            }
                        }
                    }
                }
            }

            // finally delete the link after walk-through
            $link->delete();
        }

        // remove the lock
        fclose($fp);

        return true;
    }
}
