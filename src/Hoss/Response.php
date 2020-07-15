<?php

namespace Hoss;


/**
 * Encapsulates a HTTP response.
 */
class Response
{

    protected $statusCode;
    /**
     * @var array
     */
    protected $headers = array();
    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $curlInfo = array();

    protected $httpVersion;
    protected $receivedAt;

    /**
     * @param string|array $status
     * @param array $headers
     * @param string $body
     * @param array $curlInfo
     */
    final public function __construct($statusCode, array $headers = array(), $body = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
        $this->receivedAt = round(microtime(true) * 1000);
    }

    /**
     * Returns an array representation of this Response.
     *
     * @return array Array representation of this Request.
     */
    public function toArray()
    {
        return array(
            'statusCode' => $this->statusCode,
            'headers' => $this->getHeaders(),
            'body' => base64_encode($this->getBody()),
            'receivedAt' => $this->receivedAt,
        );
    }

    /**
     * Creates a new Response from a specified array.
     *
     * @param array $response Array representation of a Response.
     * @return Response A new Response from a specified array
     */
    public static function fromArray(array $response)
    {
        $body = isset($response['body']) ? $response['body'] : null;

        $gzip = isset($response['headers']['Content-Type'])
            && strpos($response['headers']['Content-Type'], 'application/x-gzip') !== false;

        $binary = isset($response['headers']['Content-Transfer-Encoding'])
            && $response['headers']['Content-Transfer-Encoding'] == 'binary';

        // Base64 decode when binary
        if ($gzip || $binary) {
            $body = base64_decode($response['body']);
        }

        return new static(
            isset($response['status']) ? $response['status'] : 200,
            isset($response['headers']) ? $response['headers'] : array(),
            $body
        );
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getCurlInfo($option = null)
    {
        if (empty($option)) {
            return $this->curlInfo;
        }

        if (!isset($this->curlInfo[$option])) {
            return null;
        }

        return $this->curlInfo[$option];
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getContentType()
    {
        return $this->getHeader('Content-Type');
    }

    public function getHeader($key)
    {
        if (!isset($this->headers[$key])) {
            return null;
        }

        return $this->headers[$key];
    }

    /**
     * @return mixed
     */
    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
