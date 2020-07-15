<?php

namespace Hoss;

/**
 * Encapsulates a HTTP request.
 */
class Request
{
    /**
     * @var string
     */
    protected $method;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $headers = array();
    /**
     * @var string
     */
    protected $body;

    protected $receivedAt;

    /**
     * @param string $method
     * @param string $url
     * @param array $headers
     */
    public function __construct($method, $url, array $headers = array(), $body = null)
    {
        $this->method = $method;
        $this->headers = $headers;
        $this->setUrl($url);
        $this->setReceivedAt();
        if (!is_null($body)) {
            $this->setBody($body);
        }

    }


    /**
     * Returns an array representation of this request.
     *
     * @return array Array representation of this request.
     */
    public function toArray()
    {
        return array(
            'method' => $this->getMethod(),
            'url' => $this->getUrl(),
            'headers' => $this->getHeaders(),
            'body' => base64_encode($this->getBody()),
            'receivedAt' => $this->receivedAt,
        );
    }


    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
        if ($this->hasHeader('Host') === false || $this->getHeader('Host') === null) {
            $this->setHeader('Host', $this->getHost());
        }
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        if ($this->getCurlOption(CURLOPT_CUSTOMREQUEST) !== null) {
            return $this->getCurlOption(CURLOPT_CUSTOMREQUEST);
        }

        return $this->method;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getHeader($key)
    {
        return $this->headers[$key];
    }

    /**
     * @param $key
     * @return boolean
     */
    public function hasHeader($key)
    {
        return array_key_exists($key, $this->headers);
    }

    /**
     * @return array
     */
    public function getPostFields()
    {
        return $this->postFields;
    }

    /**
     * @return array
     */
    public function getPostFiles()
    {
        return $this->postFiles;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        $host = parse_url($this->getUrl(), PHP_URL_HOST);

        if ($port = parse_url($this->getUrl(), PHP_URL_PORT)) {
            $host .= ':' . $port;
        }

        return $host;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return parse_url($this->getUrl(), PHP_URL_PATH);
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return parse_url($this->getUrl(), PHP_URL_QUERY);
    }

    /**
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getCurlOption($key)
    {
        if (empty($this->curlOptions[$key])) {
            return null;
        }

        return $this->curlOptions[$key];
    }

    /**
     * Sets the request method.
     *
     * @param string $method HTTP request method like GET, POST, PUT, ...
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * @param array $post_fields
     */
    public function setPostFields(array $post_fields)
    {
        $this->postFields = $post_fields;
    }

    /**
     * @param array $post_files
     */
    public function setPostFiles(array $post_files)
    {
        $this->postFiles = $post_files;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Sets the authorization credentials as header.
     *
     * @param string $username Username.
     * @param string $password Password.
     */
    public function setAuthorization($username, $password)
    {
        $this->setHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $password));
    }

    /**
     * @param array $curlOptions
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->curlOptions = $curlOptions;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @param $key
     */
    public function removeHeader($key)
    {
        unset($this->headers[$key]);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setPostField($key, $value)
    {
        $this->postFields[$key] = $value;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setCurlOption($key, $value)
    {
        $this->curlOptions[$key] = $value;
    }

    public function addPostFile(array $file)
    {
        $this->postFiles[] = $file;
    }

    public function setReceivedAt()
    {
        $this->receivedAt = round(microtime(true) * 1000);
    }
}
