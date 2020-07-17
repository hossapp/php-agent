<?php

namespace Hoss;

class AccountApiConfiguration
{
    private $uuid;
    private $hostBlacklist = array();
    private $sanitizedHeaders = array();
    private $sanitizedQueryParams = array();
    private $sanitizedBodyFields = array();
    private $bodyCapture;

    /**
     * AccountApiConfiguration constructor.
     * @param AccountApiConfiguration $remoteAccountApiConfigu $uuidration
     */
    public function __construct($uuid, $hostBlacklist, $sanitizedBodyFields, $sanitizedQueryParams, $sanitizedHeaders, $bodyCapture)
    {
        $this->uuid = $uuid;
        $this->hostBlacklist = $hostBlacklist;
        $this->sanitizedHeaders = $sanitizedHeaders;
        $this->sanitizedQueryParams = $sanitizedQueryParams;
        $this->sanitizedBodyFields = $sanitizedBodyFields;
        $this->bodyCapture = $bodyCapture;
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return array
     */
    public function getHostBlacklist(): array
    {
        return $this->hostBlacklist;
    }

    /**
     * @return array
     */
    public function getSanitizedHeaders(): array
    {
        return $this->sanitizedHeaders;
    }

    /**
     * @return array
     */
    public function getSanitizedQueryParams(): array
    {
        return $this->sanitizedQueryParams;
    }

    /**
     * @return array
     */
    public function getSanitizedBodyFields(): array
    {
        return $this->sanitizedBodyFields;
    }

    /**
     * @return mixed
     */
    public function getBodyCapture()
    {
        return $this->bodyCapture;
    }
}

class ApiConfiguration
{
    private $uuid;
    private $sanitizedHeaders = array();
    private $sanitizedQueryParams = array();
    private $sanitizedBodyFields = array();
    private $bodyCapture = "On";

    /**
     * AccountApiConfiguration constructor.
     * @param $uuid
     * @param array $sanitizedHeaders
     * @param array $sanitizedQueryParams
     * @param array $sanitizedBodyFields
     */
    public function __construct($uuid, array $sanitizedHeaders, array $sanitizedQueryParams, string $bodyCapture, array $sanitizedBodyFields)
    {
        $this->uuid = $uuid;
        $this->sanitizedHeaders = $sanitizedHeaders;
        $this->sanitizedQueryParams = $sanitizedQueryParams;
        $this->sanitizedBodyFields = $sanitizedBodyFields;
        $this->bodyCapture = $bodyCapture;
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return array
     */
    public function getSanitizedHeaders(): array
    {
        return $this->sanitizedHeaders;
    }

    /**
     * @return array
     */
    public function getSanitizedQueryParams(): array
    {
        return $this->sanitizedQueryParams;
    }

    /**
     * @return array
     */
    public function getSanitizedBodyFields(): array
    {
        return $this->sanitizedBodyFields;
    }

    /**
     * @return string
     */
    public function getBodyCapture(): string
    {
        return $this->bodyCapture;
    }


}

class Configuration
{
    private static $defaultSanitizedKeys = array(
        'authorization',
        'password',
        'secret',
        'passwd',
        'token',
        'api_key',
        'access_token',
        'sessionid');
    private static $remoteConfigurationGraphQL = "
    query AgentConfig {
      agentConfig {
        accountApiConfiguration {
          uuid
          hostBlacklist
          sanitizedHeaders
          sanitizedQueryParams
          sanitizedBodyFields {
            type
            value
          }
          bodyCapture
        }
        apis {
          uuid
          hosts
          configuration(mergeWithAccountConfiguration: true) {
            uuid
            sanitizedHeaders
            sanitizedQueryParams
            bodyCapture
            sanitizedBodyFields {
              type
              value
            }
          }
        }
      }
    }";

    /**
     * List of library hooks.
     *
     * Format:
     * array(
     *  'name' => 'class name'
     * )
     * @var array List of library hooks.
     */
    private $availableLibraryHooks = array(
        'stream_wrapper' => 'Hoss\LibraryHooks\StreamWrapperHook',
        'curl' => 'Hoss\LibraryHooks\CurlHook',
//        'soap' => 'Hoss\LibraryHooks\SoapHook',
    );

    /**
     * Maximum number of items in the queue. Once queue is full, early items are removed
     * @var int
     */
    private $maxQueueSize = 1000;
    /**
     * Number of message per batch
     * @var int
     */
    private $batchSize = 20;
    private $apiHost = 'app.hoss.com';
    private $ingressHost = 'ingress.hoss.com';

    /**
     * If debug is enabled
     * @var bool
     */
    private $debug = false;

    /**
     * Set maximum waiting time to 1s
     * @var int
     */
    private $maxBackOffDuration = 1000;

    /**
     * Set maximum body size
     * @var int
     */
    private $maxBodySize = 512000;


    private $blackList = array('src/Hoss/LibraryHooks/', 'src/Hoss/Util/SoapClient');
    private $apiKey;

    private $remoteConfigEnabled = true;
    private $remoteConfigCacheFile = '/tmp/hoss_config.json';
    private $remoteConfigFetchInterval = 600;
    private $lastFetchTimestamp;
    # following are configurations that can be updated remotely

    private $accountAPIConfiguration;
    private $apisConfiguration = array();

    /**
     * Configuration constructor.
     * @param array $options
     */
    public function __construct($apiKey, $options = array())
    {
        $this->apiKey = $apiKey;
        if ($this->getConfig('max_queue_size', $options)) {
            $this->maxQueueSize = (int)$this->getConfig('max_queue_size', $options);
        }
        if ($this->getConfig('batch_size', $options)) {
            $this->batchSize = (int)$this->getConfig('batch_size', $options);
        }
        if ($this->getConfig('ingress_host', $options)) {
            $this->ingressHost = $this->getConfig('ingress_host', $options);
        }
        if ($this->getConfig('api_host', $options)) {
            $this->apiHost = $this->getConfig('api_host', $options);
        }
        if (!is_null($this->getConfig('debug', $options))) {
            $this->debug = (bool)$this->getConfig('debug', $options);
        }
        if ($this->getConfig('max_backoff_duration', $options)) {
            $this->maxBackOffDuration = (int)$this->getConfig('max_backoff_duration', $options);
        }
        if (!is_null($this->getConfig('remote_config_enabled', $options))) {
            $this->remoteConfigEnabled = (bool)$this->getConfig('remote_config_enabled', $options);
        }
        if ($this->getConfig('remote_config_fetch_interval', $options)) {
            $this->remoteConfigFetchInterval = (int)$this->getConfig('remote_config_fetch_interval', $options);
        }
        if ($this->getConfig('remote_config_cache_file', $options)) {
            $this->remoteConfigCacheFile = $this->getConfig('remote_config_fetch_interval', $options);
        }
        $this->accountAPIConfiguration = new AccountApiConfiguration("default", array(), array(), self::$defaultSanitizedKeys, self::$defaultSanitizedKeys, "On");
    }

    /**
     * Get config value from options array or from environment variable in that order
     * @param $name : name of the variable in snake case. Environment variable name is upper case version with HOSS_ prefix added
     * @param $options
     * @return array|false|mixed|string
     */
    private function getConfig($name, $options)
    {
        if (isset($options[$name])) {
            return $options[$name];
        }
        $envName = 'HOSS_' . strtoupper($name);
        if (getenv($envName) !== false) {
            return getenv($envName);
        }
        return null;
    }

    /**
     * Returns a list of enabled LibraryHook class names.
     *
     * Only class names are returned, any object creation happens
     * in the VCRFactory.
     *
     * @return string[] List of LibraryHook class names.
     */
    public function getLibraryHooks()
    {
        return $this->availableLibraryHooks;
    }

    public function getWhitelist()
    {
        return array();
    }

    public function getBlackList()
    {
        return $this->blackList;
    }

    /**
     * @return array|false|int|mixed|string
     */
    public function getMaxQueueSize()
    {
        return $this->maxQueueSize;
    }

    /**
     * @return array|false|int|mixed|string
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @return array|false|mixed|string
     */
    public function getApiHost()
    {
        return $this->apiHost;
    }

    /**
     * @return array|false|mixed|string
     */
    public function getIngressHost()
    {
        return $this->ingressHost;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return int
     */
    public function getMaxBackOffDuration(): int
    {
        return $this->maxBackOffDuration;
    }


    /**
     * @return mixed
     */
    public function getAccountAPIConfiguration(): ?AccountApiConfiguration
    {
        return $this->accountAPIConfiguration;
    }

    /**
     * @return mixed
     */
    public function getApisConfiguration(): array
    {
        return $this->apisConfiguration;
    }

    private function updateFromRemoteConfig($remoteConfigData)
    {
        // todo handle no config
        if ($remoteConfigData && !array_key_exists('errors', $remoteConfigData) && array_key_exists('data',$remoteConfigData) && array_key_exists('agentConfig',$remoteConfigData['data'])) {
            $remoteConfig = $remoteConfigData['data']['agentConfig'];
            $accountApiConfiguration = $remoteConfig['accountApiConfiguration'] ;
            $apis = $remoteConfig['apis'];
            if (!is_null($accountApiConfiguration)) {
                $this->accountAPIConfiguration = new AccountApiConfiguration(
                    $accountApiConfiguration['uuid'],
                    $accountApiConfiguration['hostBlacklist'],
                    $accountApiConfiguration['sanitizedBodyFields'],
                    $accountApiConfiguration['sanitizedQueryParams'],
                    $accountApiConfiguration['sanitizedHeaders'],
                    $accountApiConfiguration['bodyCapture']
                );
            }
            if (!is_null($apis)) {
                $this->apisConfiguration = array();

                foreach ($apis as $api) {
                    $conf = $api['configuration'];
                    $apiConfiguration = new ApiConfiguration(
                        $api['uuid'],
                        $conf['sanitizedHeaders'],
                        $conf['sanitizedQueryParams'],
                        $conf['bodyCapture'],
                        $conf['sanitizedBodyFields']
                    );
                    # map host to api configuration
                    foreach ($api['hosts'] as $host) {
                        $this->apisConfiguration[$host] = $apiConfiguration;
                    }
                }
            }
        }
    }

    /**
     * Return timestamp of the cached config
     */
    private function getCachedConfigTimestamp()
    {
        try {
            return filemtime($this->remoteConfigCacheFile);
        } catch (\Exception $e) {
            if ($this->isDebug()) {
                $message = "Error getting last modified time: ".$e->getMessage();
                error_log('[Hoss][Configuration] '.$message);
            }

        }
        return false;

    }

    private function updateConfigFromCache()
    {
        $configString = file_get_contents($this->remoteConfigCacheFile);
        if ($configString === false) {
            return;
        }
        $config = json_decode($configString, true);
        $this->updateFromRemoteConfig($config);
    }

    /**
     * Fetch remote config and update local config if different
     */
    public function fetchRemoteConfig()
    {
        global $HOSS_VERSION;
        if ($this->remoteConfigEnabled) {
            $cachedConfigTimestamp = $this->getCachedConfigTimestamp() * 1000;
            $now = round(microtime(true) * 1000);
            if ($cachedConfigTimestamp !== false && $now - $cachedConfigTimestamp <= $this->remoteConfigFetchInterval * 1000) {
                $this->updateConfigFromCache();
                return;
            }

            $body = array(
                "query" => Configuration::$remoteConfigurationGraphQL
            );
            $payload = json_encode($body);

            $protocol = "https://";
            $path = "/api/graphql";
            $url = $protocol . $this->getApiHost() . $path;

            // open connection
            $ch = \curl_init();

            // set the url, number of POST vars, POST data
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            // set variables for headers
            $header = array();
            $header[] = 'Content-Type: application/json';
            $header[] = "User-Agent: hoss-php/${HOSS_VERSION}";
            $header[] = 'Authorization: Bearer ' . $this->apiKey;

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $httpResponse = \curl_exec($ch);

            $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //close connection
            curl_close($ch);

            if (200 == $httpCode) {
                # only write cache file if config doesn't have error
                $decodedConfig = json_decode($httpResponse, true);
                if (!array_key_exists("errors", $decodedConfig) && array_key_exists("data", $decodedConfig) && array_key_exists("agentConfig", $decodedConfig["data"])) {
                    $result = file_put_contents($this->remoteConfigCacheFile, $httpResponse);
                    $this->updateFromRemoteConfig(json_decode($httpResponse, true));
                } else {
                    if ($this->isDebug()) {
                        $message = "Error getting remote config: ".$httpResponse;
                        error_log('[Hoss][Configuration] '.$message);
                    }
                }
            } else {
                if ($this->isDebug()) {
                    $message = "Error getting remote config: ".$httpResponse;
                    error_log('[Hoss][Configuration] '.$message);
                }
            }
            $this->lastFetchTimestamp = round(microtime(true) * 1000);

            return $httpResponse;
        }
    }

    /**
     * @return int
     */
    public function getMaxBodySize(): int
    {
        return $this->maxBodySize;
    }
}

?>
