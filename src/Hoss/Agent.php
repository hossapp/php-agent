<?php

namespace Hoss;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require_once(__DIR__ . '/Client.php');
require_once(__DIR__ . '/Configuration.php');
require_once(__DIR__ . '/Version.php');
require_once(__DIR__ . '/LibraryHooks/CurlHook.php');
require_once(__DIR__ . '/LibraryHooks/StreamWrapperHook.php');
//require_once(__DIR__ . '/LibraryHooks/SoapHook.php');

class Agent
{
    private $client;
    private $currentEvent;

    /**
     * Agent constructor.
     */
    public function __construct($apiKey, $options = array())
    {
        if (!$apiKey) {
            // log error
            return;
        }
        $this->client = new Client($apiKey, new Configuration($apiKey, $options)); // need to pass in options
    }

//    public function GuzzleRequestMapper(RequestInterface $request)
//    {
//        $this->currentEvent = new Event(new Request($request->getMethod(), (string)$request->getUri(), $request->getHeaders(), $request->getBody()->getContents()));
//        return $request;
//    }
//
//    public function GuzzleResponseMapper(ResponseInterface $response)
//    {
//        try {
//            if (!is_null($this->currentEvent)) {
//                $this->currentEvent->response = new Response($response->getStatusCode(), $response->getHeaders(), $response->getBody()->getContents());
//            }
//            $this->client->queue($this->currentEvent);
//        } catch (Throwable $e) {
//            error_log('[Hoss] Caught error in parsing response: ' . $e->getMessage());
//        }
//        return $response;
//    }

    /**
     * Captures a HTTP request. This method returns an $event handler that captureResponse method requires
     * @param $method
     * @param $url
     * @param array $headers An array of request headers
     * @param null $body Body as a string
     * @return Event
     */
    public function captureRequest($method, $url, $headers=array(), $body=null) {
        return new Event(new Request($method, $url, $headers, $body));
    }

    /**
     * Capture a HTTP response given an $event handler returned by captureRequest. This method will queue up the event to be
     * consumed by a QueueConsumer
     * @param Event $event
     * @param $responseStatusCode
     * @param $responseHeaders
     * @param $responseBody
     */
    public function captureResponse(Event $event, $responseStatusCode, $responseHeaders, $responseBody) {
        $event->response = new Response($responseStatusCode, $responseHeaders, $responseBody);
        $this->client->queue($event);
    }

    /**
     * Instrument outbound http calls. See list of supported instrumentation at https://docs.hoss.com/agents/php
     * @param $apiKey
     * @param array $options
     */
    public static function instrument($apiKey, $options = array())
    {
        $instance = new Agent($apiKey, $options);

        foreach ($instance->client->configuration->getLibraryHooks() as $hookClass) {
            $hook = new $hookClass($instance->client);
            $hook->enable();
        }
    }
}

?>
