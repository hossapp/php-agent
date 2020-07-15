<?php

namespace Hoss;

require_once(__DIR__.'/Request.php');
require_once(__DIR__.'/Response.php');

use Hoss\Util\TextUtil;

class Event {
    public $eventId;
    public $request;
    public $response;
    public $error = null;
    private $metadata = array();
    /**
     * Event constructor.
     */
    public function __construct(Request $request)
    {
        $this->eventId = TextUtil::generateUUID();
        $this->request = $request;
    }

    public function setReseponse(Response $response)
    {
        $this->response = $response;
    }

    public function setError(Error $error)
    {
        $this->error = $error;
    }

    public function toArray()
    {
        return array(
            'eventId' => $this->eventId,
            'request' => $this->request->toArray(),
            'response' => $this->response->toArray(),
            'metadata' => $this->metadata,
        );
    }

    public function hasResponse()
    {
        return !is_null($this->response);
    }

    /**
     * Update metadata with id of the current api configuration
     */
    public function setApiConfigurationUUID(string $uuid) {
        $this->metadata["apiConfigurationUUID"] = $uuid;
    }
    public function removeRequestBody($reason)
    {
        $this->request->setBody("");
        $this->metadata["requestBodyNotCaptured"] = true;
        $this->metadata["requestBodyNotCapturedReason"] = $reason;

    }

    public function removeResponseBody($reason)
    {
        if ($this->hasResponse()) {
            $this->response->setBody("");
            $this->metadata["responseBodyNotCaptured"] = true;
            $this->metadata["responseBodyNotCapturedReason"] = $reason;
        }
    }

    public function removeBody($reason)
    {
        $this->removeRequestBody($reason);
        $this->removeResponseBody($reason);
    }

    public function isError()
    {
        $haveError = is_null($this->error);
        $errorStatusCode = $this->response->getStatusCode() >= 400;
        return $haveError || $errorStatusCode;
    }
}

class Error {
    private $type;
    private $context;
    private $receivedAt;

    /**
     * Error constructor.
     * @param $type
     * @param $context
     * @param $receivedAt
     */
    public function __construct($type, $context, $receivedAt)
    {
        $this->type = $type;
        $this->context = $context;
        $this->receivedAt = $receivedAt;
    }


}
