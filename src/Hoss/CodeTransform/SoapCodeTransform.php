<?php

namespace Hoss\CodeTransform;

class SoapCodeTransform extends AbstractCodeTransform
{
    const NAME = 'vcr_soap';

    private static $replacements = array(
        'new \App\Agent\Util\SoapClient(',
        'extends \App\Agent\Util\SoapClient',
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
