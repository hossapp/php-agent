<?php

namespace Hoss\CodeTransform;

class SoapCodeTransform extends AbstractCodeTransform
{
    const NAME = 'vcr_soap';

    private static $replacements = array(
        'new \Hoss\Util\SoapClient(',
        'extends \Hoss\Util\SoapClient',
    );

    private static $patterns = array(
        '@new\s+\\\?SoapClient\W*\(@i',
        '@extends\s+\\\?SoapClient@i',
    );

    /**
     * @inheritdoc
     */
    protected function transformCode($code)
    {
        return preg_replace(self::$patterns, self::$replacements, $code);
    }
}
