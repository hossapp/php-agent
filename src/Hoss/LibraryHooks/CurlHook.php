<?php

namespace Hoss\LibraryHooks;

require_once(__DIR__ . '/LibraryHook.php');
require_once(__DIR__ . '/../Util/StreamProcessor.php');
require_once(__DIR__ . '/../CodeTransform/CurlCodeTransform.php');
require_once(__DIR__ . '/../Event.php');

use Hoss\CodeTransform\CurlCodeTransform;
use Hoss\Client;
use Hoss\Event;
use Hoss\Request;
use Hoss\Response;
use Hoss\CodeTransform\AbstractCodeTransform;
use Hoss\Util\CurlHelper;
use Hoss\Util\StreamProcessor;
use Hoss\Util\TextUtil;
use Hoss\Util\HttpUtil;

/**
 * Library hook for curl functions using include-overwrite.
 */
class CurlHook implements LibraryHook
{
    /**
     * @var \Closure Callback which will be executed when a request is intercepted.
     */
    protected static $requestCallback;

    /**
     * @var string Current status of this hook, either enabled or disabled.
     */
    protected static $status = self::DISABLED;

    /**
     * @var Request[] All events which have been intercepted.
     */
    protected static $events = array();

    /**
     * @var array Additinal curl options, which are not stored within a request.
     */
    protected static $curlOptions = array();

    /**
     * @var array store curl headers so we can parse them in one pass
     */
    protected static $curlResponseHeaders = array();

    /**
     * @var array All curl handles which belong to curl_multi handles.
     */
    protected static $multiHandles = array();

    /**
     * @var array Last active curl_multi_exec() handles.
     */
    protected static $multiExecLastChs = array();

    protected static $client;

    /**
     * @var AbstractCodeTransform
     */
    private $codeTransformer;

    /**
     * @var StreamProcessor
     */
    private $processor;

    /**
     * Creates a new cURL hook instance.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        if (!function_exists('curl_version')) {
            // @codeCoverageIgnoreStart
            throw new \BadMethodCallException(
                'cURL extension not installed, please disable the cURL library hook'
            );
            // @codeCoverageIgnoreEnd
        }
        $this->processor = StreamProcessor::getInstance($client->configuration);
        $this->codeTransformer = new CurlCodeTransform();
        static::$client = $client;
    }

    /**
     * @inheritDoc
     */
    public function enable()
    {
//        Assertion::isCallable($requestCallback, 'No valid callback for handling requests defined.');

        if (static::$status == self::ENABLED) {
            return;
        }

        $this->codeTransformer->register();
        $this->processor->appendCodeTransformer($this->codeTransformer);
        $this->processor->intercept();

        static::$status = self::ENABLED;
    }

    /**
     * @inheritDoc
     */
    public function disable()
    {
        if (static::$status == self::DISABLED) {
            return;
        }

        self::$requestCallback = null;

        static::$status = self::DISABLED;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return self::$status == self::ENABLED;
    }

    private static function parseResponseHeaders($curlResponse)
    {
        if ($curlResponse === false || $curlResponse == "") {
            return null;
        }
        list($status, $headers, $body) = HttpUtil::parseResponse($curlResponse);
        return new Response(
            HttpUtil::parseStatus($status),
            HttpUtil::parseHeaders($headers),
            $body
        );
    }

    //====================
    // Curl function hooks
    //====================

    /**
     * Calls the intercepted curl method if library hook is disabled, otherwise the real one.
     *
     * @param string $method cURL method to call, example: curl_info()
     * @param array $args cURL arguments for this function.
     *
     * @return mixed  cURL function return type.
     */
    public static function __callStatic($method, $args)
    {
        // Call original when disabled
        if (static::$status == self::DISABLED) {
            if ($method === 'curl_multi_exec') {
                // curl_multi_exec expects to be called with args by reference
                // which call_user_func_array doesn't do.
                return \curl_multi_exec($args[0], $args[1]);
            }

            return \call_user_func_array($method, $args);
        }

        if ($method === 'curl_multi_exec') {
            // curl_multi_exec expects to be called with args by reference
            // which call_user_func_array doesn't do.
            return self::curlMultiExec($args[0], $args[1]);
        }

        $localMethod = TextUtil::underscoreToLowerCamelcase($method);
        return \call_user_func_array(array(__CLASS__, $localMethod), $args);
    }

    /**
     * Initialize a cURL session.
     *
     * @link http://www.php.net/manual/en/function.curl-init.php
     * @param string $url (Optional) url.
     *
     * @return resource cURL handle.
     */
    public static function curlInit($url = null)
    {
        $curlHandle = \curl_init($url);
        try {
            self::$events[(int)$curlHandle] = new Event(new Request('GET', $url));
            self::$curlOptions[(int)$curlHandle] = array();
        } catch (\Exception $e) {
        }
        return $curlHandle;
    }

    /**
     * Reset a cURL session.
     *
     * @link http://www.php.net/manual/en/function.curl-reset.php
     * @param resource $curlHandle A cURL handle returned by curl_init().
     */
    public static function curlReset($curlHandle)
    {
        \curl_reset($curlHandle);
        try {
            self::$events[(int)$curlHandle] = new Event(new Request('GET', null));
            self::$curlOptions[(int)$curlHandle] = array();
        } catch (\Throwable $e) {
        } catch (\Exception $e) {
        }
    }

    private static function createHeaderFunction($ch)
    {
        $options = self::$curlOptions[(int)$ch];
        return function ($ch, $h) use (
            $options
        ) {
            $val = trim($h);
            if ($val !== '' && strpos("HTTP/1.1 100 Continue", $val) === false) {
                $idx = (int)$ch;
                if (!array_key_exists($idx, self::$curlResponseHeaders)) {
                    self::$curlResponseHeaders[$idx] = array();
                }
                array_push(self::$curlResponseHeaders[$idx], $val);
            }
            if (array_key_exists(CURLOPT_HEADERFUNCTION, $options)) {
                return $options[CURLOPT_HEADERFUNCTION]($ch, $h);
            }
            return strlen($h);
        };
    }

    /**
     * Perform a cURL session.
     *
     * @link http://www.php.net/manual/en/function.curl-exec.php
     * @param resource $curlHandle A cURL handle returned by curl_init().
     *
     * @return mixed Returns TRUE on success or FALSE on failure.
     * However, if the CURLOPT_RETURNTRANSFER option is set, it will return the
     * result on success, FALSE on failure.
     */
    public static function curlExec($curlHandle)
    {
        $curlHandleIndex = (int)$curlHandle;
        $key = $curlHandleIndex;
        $event = null;
        try {
            if (array_key_exists($key, self::$events)) {
                $event = self::$events[$key];
                $event->request->setReceivedAt();
            }
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        # override header function with our version so we can access the header. The function call through to the original
        # header function if available
        \curl_setopt($curlHandle, CURLOPT_HEADERFUNCTION, self::createHeaderFunction($curlHandle, $event));

        $result = \curl_exec($curlHandle);

        try {
            // self::curlResponseHeaders contains an array of trimmed headers strings at this point
            // parse the status code & headers
            if (!is_null($event)) {
                $headers = self::$curlResponseHeaders[$curlHandleIndex];
                $status = array_shift($headers);
                if (array_key_exists(CURLOPT_FILE, self::$curlOptions[$curlHandleIndex])) {
                    $body = stream_get_contents(self::$curlOptions[$curlHandleIndex][CURLOPT_FILE]);
                } elseif (array_key_exists(CURLOPT_RETURNTRANSFER, self::$curlOptions[$curlHandleIndex])) {
                    $body = $result;
                }
                $event->response = new Response(HttpUtil::parseStatus($status), HttpUtil::parseHeaders($headers), $body);
            }
            self::$client->queue($event);

        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        // if we got exception somehow, still try to return the $result
        return $result;
    }

    /**
     * Set an option for a cURL transfer.
     *
     * @link http://www.php.net/manual/en/function.curl-setopt.php
     * @param resource $curlHandle A cURL handle returned by curl_init().
     * @param integer $option The CURLOPT_XXX option to set.
     * @param mixed $value The value to be set on option.
     *
     * @return boolean  Returns TRUE on success or FALSE on failure.
     */
    public static function curlSetopt($curlHandle, $option, $value)
    {
        CurlHelper::setCurlOptionOnRequest(self::$events[(int)$curlHandle]->request, $option, $value, $curlHandle);

        static::$curlOptions[(int)$curlHandle][$option] = $value;
        return \curl_setopt($curlHandle, $option, $value);
    }

    /**
     * Set multiple options for a cURL transfer.
     *
     * @link http://www.php.net/manual/en/function.curl-setopt-array.php
     * @param resource $curlHandle A cURL handle returned by curl_init().
     * @param array $options An array specifying which options to set and their values.
     */
    public static function curlSetoptArray($curlHandle, $options)
    {
        try {
            if (is_array($options)) {
                foreach ($options as $option => $value) {
                    static::curlSetopt($curlHandle, $option, $value);
                }
            }
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }
}
