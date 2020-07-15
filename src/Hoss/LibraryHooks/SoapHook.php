<?php

namespace Hoss\LibraryHooks;

use Hoss\CodeTransform\SoapCodeTransform;
use Hoss\VCRException;
use Hoss\Request;
use Hoss\CodeTransform\AbstractCodeTransform;
use Hoss\Util\StreamProcessor;

/**
 * Library hook for curl functions.
 */
class SoapHook implements LibraryHook
{
    /**
     * @var callable
     */
    private static $requestCallback;

    /**
     * @var string
     */
    private $status = self::DISABLED;

    /**
     * @var AbstractCodeTransform
     */
    private $codeTransformer;

    /**
     * @var \App\Hoss\Util\StreamProcessor
     */
    private $processor;

    /**
     * Creates a SOAP hook instance.
     *
     * @param AbstractCodeTransform $codeTransformer
     * @param StreamProcessor $processor
     *
     * @throws \BadMethodCallException in case the Soap extension is not installed.
     */
    public function __construct()
    {
        if (!class_exists('\SoapClient')) {
            throw new \BadMethodCallException('For soap support you need to install the soap extension.');
        }

        if (!class_exists('\DOMDocument')) {
            throw new \BadMethodCallException('For soap support you need to install the xml extension.');
        }

        $this->processor = StreamProcessor::getInstance();
        $this->codeTransformer = new SoapCodeTransform();
    }

    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param integer $version
     * @param int $one_way
     *
     * @return string SOAP response.
     * @throws \App\Hoss\VCRException It this method is called although VCR is disabled.
     *
     */
    public function doRequest($request, $location, $action, $version, $one_way = 0, $options = array())
    {
        if ($this->status === self::DISABLED) {
            throw new VCRException('Hook must be enabled.', VCRException::LIBRARY_HOOK_DISABLED);
        }

        $vcrRequest = new Request('POST', $location);

        if ($version === SOAP_1_1) {
            $vcrRequest->setHeader('Content-Type', 'text/xml; charset=utf-8;');
            $vcrRequest->setHeader('SOAPAction', $action);
        } else { // >= SOAP_1_2
            $vcrRequest->setHeader(
                'Content-Type',
                sprintf('application/soap+xml; charset=utf-8; action="%s"', $action)
            );
        }

        $vcrRequest->setBody($request);

        if (!empty($options['login'])) {
            $vcrRequest->setAuthorization($options['login'], $options['password']);
        }

        /* @var \App\Hoss\Response $response */
        $requestCallback = self::$requestCallback;
        $response = $requestCallback($vcrRequest);

        return $response->getBody();
    }

    /**
     * @inheritDoc
     */
    public function enable()
    {
        if ($this->status == self::ENABLED) {
            return;
        }

        $this->codeTransformer->register();
        $this->processor->appendCodeTransformer($this->codeTransformer);
        $this->processor->intercept();

        $this->status = self::ENABLED;
    }

    /**
     * @inheritDoc
     */
    public function disable()
    {
        if (!$this->isEnabled()) {
            return;
        }

        self::$requestCallback = null;

        $this->status = self::DISABLED;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return $this->status == self::ENABLED;
    }

    /**
     * Cleanup.
     *
     * @return  void
     */
    public function __destruct()
    {
        self::$requestCallback = null;
    }
}
