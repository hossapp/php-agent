<?php
namespace Hoss\Consumers;

require_once(__DIR__.'/BaseQueueConsumer.php');


class LibCurlConsumer extends BaseQueueConsumer
{
    protected function flushBatch($messages)
    {
        global $HOSS_VERSION;
        $body = $this->payload($messages);
        $payload = gzencode(json_encode($body));

        $protocol = "https://";
        $path = "/v1";
        $url = $protocol . $this->configuration->getIngressHost() . $path;

        $backoff = 100;     // Set initial waiting time to 100ms

        while ($backoff < $this->configuration->getMaxBackOffDuration()) {
            $start_time = microtime(true);

            // open connection
            $ch = \curl_init();

            // set the url, number of POST vars, POST data
            \curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            // set variables for headers
            $header = array();
            $header[] = 'Content-Type: application/json';
            $header[] = 'Content-Encoding: gzip';
            $header[] = "User-Agent: hoss-php/${HOSS_VERSION}";
            $header[] = 'Authorization: Bearer '.$this->apiKey;

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // retry failed requests just once to diminish impact on performance
            $httpResponse = $this->executePost($ch);

            //close connection
            curl_close($ch);

            $elapsed_time = microtime(true) - $start_time;

            if (200 != $httpResponse) {
                // log error
                $this->handleError($ch, $httpResponse);

                if (($httpResponse >= 500 && $httpResponse <= 600) || 429 == $httpResponse) {
                    // If status code is greater than 500 and less than 600, it indicates server error
                    // Error code 429 indicates rate limited.
                    // Retry uploading in these cases.
                    usleep($backoff * 1000);
                    $backoff *= 2;
                } elseif ($httpResponse >= 400) {
                    break;
                }
            } else {
                break;  // no error
            }
        }

        return $httpResponse;
    }

    public function executePost($ch) {
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $httpCode;
    }

}
