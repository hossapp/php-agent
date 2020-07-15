<?php

namespace Hoss\CodeTransform;
require_once(__DIR__.'/AbstractCodeTransform.php');

class CurlCodeTransform extends AbstractCodeTransform
{
    const NAME = 'vcr_curl';

    private static $patterns = array(
        '/(?<!::|->|\w_)\\\?curl_init\s*\(/i'                => '\Hoss\LibraryHooks\CurlHook::curl_init(',
        '/(?<!::|->|\w_)\\\?curl_exec\s*\(/i'                => '\Hoss\LibraryHooks\CurlHook::curl_exec(',
//        '/(?<!::|->|\w_)\\\?curl_getinfo\s*\(/i'             => '\Hoss\LibraryHooks\CurlHook::curl_getinfo(',
        '/(?<!::|->|\w_)\\\?curl_setopt\s*\(/i'              => '\Hoss\LibraryHooks\CurlHook::curl_setopt(',
        '/(?<!::|->|\w_)\\\?curl_setopt_array\s*\(/i'        => '\Hoss\LibraryHooks\CurlHook::curl_setopt_array(',
//        '/(?<!::|->|\w_)\\\?curl_multi_add_handle\s*\(/i'    => '\Hoss\LibraryHooks\CurlHook::curl_multi_add_handle(',
//        '/(?<!::|->|\w_)\\\?curl_multi_remove_handle\s*\(/i' => '\Hoss\LibraryHooks\CurlHook::curl_multi_remove_handle(',
//        '/(?<!::|->|\w_)\\\?curl_multi_exec\s*\(/i'          => '\Hoss\LibraryHooks\CurlHook::curl_multi_exec(',
//        '/(?<!::|->|\w_)\\\?curl_multi_info_read\s*\(/i'     => '\Hoss\LibraryHooks\CurlHook::curl_multi_info_read(',
        '/(?<!::|->|\w_)\\\?curl_reset\s*\(/i'               => '\Hoss\LibraryHooks\CurlHook::curl_reset('
    );

    /**
     * @inheritdoc
     */
    protected function transformCode($code)
    {
        $preg_replace = preg_replace(array_keys(self::$patterns), array_values(self::$patterns), $code);
        return $preg_replace;
    }
}
