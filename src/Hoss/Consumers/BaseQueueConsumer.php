<?php
namespace Hoss\Consumers;

use Hoss\Configuration;
use Hoss\Event;
use Hoss\Util\MetadataUtil;

require_once(__DIR__.'/../Util/MetadataUtil.php');
require_once(__DIR__.'/../Event.php');
require_once(__DIR__.'/../Configuration.php');

abstract class BaseQueueConsumer {
    protected $type = "BaseQueueConsumer";
    protected $queue;
    protected $configuration;
    /**
     * @var array
     */
    protected $options;

    public function __construct($apiKey, Configuration $configuration)
    {
        $this->apiKey = $apiKey;
        $this->configuration = $configuration;
        $this->queue = array();
    }

    public function __destruct() {
        // Flush our queue on destruction
        $this->flush();
    }

    /**
     * Adds an event to our queue.
     * @param  mixed   $item
     * @return boolean whether call has succeeded
     */
     public function queue(Event $event) {
        $count = count($this->queue);

        if ($count >= $this->configuration->getMaxQueueSize()) {
            # drop first item
            array_shift($this->queue);
        }

        $count = array_push($this->queue, $event);


        $this->configuration->fetchRemoteConfig();


        if ($count >= $this->configuration->getBatchSize()) {
            return $this->flush(); // return ->flush() result: true on success
        }

        return true;
    }

    abstract protected function flushBatch($messages);
    /**
     * Flushes our queue of messages by batching them to the server
     */
    public function flush() {
        $count = count($this->queue);
        $success = true;

        while ($count > 0 && $success) {
            $batch = array_splice($this->queue, 0, min($this->configuration->getBatchSize(), $count));
            $success = $this->flushBatch($batch);
            $count = count($this->queue);
        }

        return $success;
    }

    /**
     * On an error, try and call the error handler, if debugging output to
     * error_log as well.
     * @param  string $code
     * @param  string $msg
     */
    protected function handleError($code, $msg) {
        if ($this->configuration->isDebug()) {
            error_log("[Hoss][" . $this->type . "] " . $msg);
        }
    }

    /**
     * Helper to process event. Return null if event should not be sent. Event is modified in place
     * @param Event $event
     * @return Event|null
     */
    private function processEvent(Event $event) {
        $accountConfiguration = $this->configuration->getAccountAPIConfiguration();
        $apiConfigurationMap = $this->configuration->getApisConfiguration();
        $apiConfiguration = null;
        if (array_key_exists($event->request->getHost(), $apiConfigurationMap)) {
            $apiConfiguration = $apiConfigurationMap[$event->request->getHost()];
            $event->setApiConfigurationUUID($apiConfiguration->getUuid());
        } else {
            $event->setApiConfigurationUUID(null);
        }

        // todo should refactor this into a list of processor functions
        if ($accountConfiguration) {
            # host block list
            foreach($accountConfiguration->getHostBlacklist() as $blockedHost) {
                if (strpos($event->request->getHost(), $blockedHost) !== false) {
                    return null;
                }
            }
        }

        # payload size
        if (strlen($event->request->getBody()) > $this->configuration->getMaxBodySize()) {
            $event->removeRequestBody("MaxSizeExceeded");
        }

        if ($event->hasResponse() && strlen($event->response->getBody()) > $this->configuration->getMaxBodySize()) {
            $event->removeResponseBody("MaxSizeExceeded");
        }

        # payload capture
        $bodyCapture = null;
        if (!is_null($accountConfiguration)) {
            $bodyCapture = $accountConfiguration->getBodyCapture();
        }
        if (!is_null($apiConfiguration)) {
            $bodyCapture = $apiConfiguration->getBodyCapture();
        }
        if ($bodyCapture == "Off") {
            $event->removePayload();
        } elseif ($bodyCapture == "OnError") {
            if (!$event->isError()) {
                $event->removeBody($bodyCapture."BodyCapture");
            }
        }

        return $event;
    }

    /**
     * Given a batch of messages the method returns
     * a valid payload.
     *
     * @param {Array} $batch
     * @return {Array}
     */
    protected function payload($batch){
        $events = array();
        foreach ($batch as $event) {
            $event = $this->processEvent($event);
            if (!is_null($event)) {
                array_push($events, $event->toArray());
            }
        }
        return array(
            "events" => $events,
            "metadata" => MetadataUtil::getRuntimeEnvironment()
        );
    }
}
