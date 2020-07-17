<?php
namespace Hoss\Consumers;

use Hoss\Configuration;

require_once(__DIR__.'/BaseQueueConsumer.php');


class SocketConsumer extends BaseQueueConsumer
{
    protected $type = "SocketConsumer";
    /**
     * @var int
     */
    private $timeout;
    /**
     * @var bool
     */
    private $socket_failed;

    /**
     * SocketConsumer constructor.
     */
    public function __construct($apiKey, Configuration $configuration)
    {
        $this->timeout = 5;
        $this->socket_failed = false;
        parent::__construct($apiKey, $configuration);
    }

    private function createSocket() {
        if ($this->socket_failed) {
            return false;
        }

        $protocol = "ssl";
        $host = $this->configuration->getIngressHost();
        $port = 443;

        try {
            // Open our socket to the API Server.
            // Since we're try catch'ing prevent PHP logs.
            $socket = @pfsockopen(
                $protocol . "://" . $host,
                $port,
                $errno,
                $errstr,
                $this->timeout
            );

            // If we couldn't open the socket, handle the error.
            if (false === $socket) {
                $this->handleError($errno, $errstr);
                $this->socket_failed = true;

                return false;
            }

            return $socket;
        } catch (Exception $e) {
            $this->handleError($e->getCode(), $e->getMessage());
            $this->socket_failed = true;

            return false;
        }
    }

    protected function flushBatch($messages)
    {
        $socket = $this->createSocket();

        if (!$socket) {
            return;
        }

        $payload = $this->payload($messages);
        $payload = json_encode($payload);

        $body = $this->createBody($this->configuration->getIngressHost(), $payload);
        if (false === $body) {
            return false;
        }

        return $this->makeRequest($socket, $body);
    }

    /**
     * Create the body to send as the post request.
     * @param  string $host
     * @param  string $content
     * @return string body
     */
    private function createBody($host, $content) {
        global $HOSS_VERSION;

        $req = "";
        $req.= "POST /v1 HTTP/1.1\r\n";
        $req.= "Host: " . $host . "\r\n";
        $req.= "Content-Type: application/json\r\n";
        $req.= "Authorization: Bearer ".$this->apiKey."\r\n";
        $req.= "User-Agent: hoss-php/${HOSS_VERSION}"."\r\n";
        $req.= "Accept: application/json\r\n";
        $req.= "Content-Encoding: gzip\r\n";
        // Compress content if compress_request is true
        $content = gzencode($content);


        $req.= "Content-length: " . strlen($content) . "\r\n";
        $req.= "\r\n";
        $req.= $content;

        // Verify message size is below than 32KB
        if (strlen($req) >= 32 * 1024) {
            if ($this->configuration->isDebug()) {
                $msg = "Message size is larger than 32KB";
                error_log("[Analytics][" . $this->type . "] " . $msg);
            }

            return false;
        }

        return $req;
    }

    /**
     * Attempt to write the request to the socket, wait for response if debug
     * mode is enabled.
     * @param  stream  $socket the handle for the socket
     * @param  string  $req    request body
     * @param  boolean $retry
     * @return boolean $success
     */
    private function makeRequest($socket, $req, $retry = true) {
        $bytes_written = 0;
        $bytes_total = strlen($req);
        $closed = false;

        // Retries with exponential backoff until success
        $backoff = 100;   // Set initial waiting time to 100ms

        while (true) {
            // Send request to server
            while (!$closed && $bytes_written < $bytes_total) {
                try {
                    // Since we're try catch'ing prevent PHP logs.
                    $written = @fwrite($socket, substr($req, $bytes_written));
                } catch (Exception $e) {
                    $this->handleError($e->getCode(), $e->getMessage());
                    $closed = true;
                }
                if (!isset($written) || !$written) {
                    $closed = true;
                } else {
                    $bytes_written += $written;
                }
            }

            // Get response for request
            $statusCode = 0;
            $errorMessage = "";

            if (!$closed) {
                $res = $this->parseResponse(fread($socket, 2048));
                $statusCode = (int)$res["status"];
                $errorMessage = $res["message"];
            }
            fclose($socket);

            // If status code is 200, return true
            if (200 == $statusCode) {
                return true;
            }

            // If status code is greater than 500 and less than 600, it indicates server error
            // Error code 429 indicates rate limited.
            // Retry uploading in these cases.
            if (($statusCode >= 500 && $statusCode <= 600) || 429 == $statusCode || 0 == $statusCode) {
                if ($backoff >= $this->maximum_backoff_duration) {
                    break;
                }

                usleep($backoff * 1000);
            } elseif ($statusCode >= 400) {
                if ($this->configuration->isDebug()) {
                    $this->handleError($res["status"], $res["message"]);
                }

                break;
            }

            // Retry uploading...
            $backoff *= 2;
            $socket = $this->createSocket();
        }
    }

    /**
     * Parse our response from the server, check header and body.
     * @param  string $res
     * @return array
     *     string $status  HTTP code, e.g. "200"
     *     string $message JSON response from the api
     */
    private function parseResponse($res) {
        $contents = explode("\n", $res);

        // Response comes back as HTTP/1.1 200 OK
        // Final line contains HTTP response.
        $status = explode(" ", $contents[0], 3);
        $result = $contents[count($contents) - 1];

        return array(
            "status"  => isset($status[1]) ? $status[1] : null,
            "message" => $result
        );
    }
}
