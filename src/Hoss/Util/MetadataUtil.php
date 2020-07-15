<?php

namespace Hoss\Util;

class MetadataUtil
{
    public static function getRuntimeEnvironment()
    {
        return array(

            "arch" => php_uname('m'),
            "hostname" => php_uname('n'),
            "platform=>" => php_uname('v'),
            "version" => phpversion(),
        );
    }
}
