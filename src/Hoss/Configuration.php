<?php

namespace Hoss;

class Configuration
{
    /**
     * List of library hooks.
     *
     * Format:
     * array(
     *  'name' => 'class name'
     * )
     * @var array List of library hooks.
     */
    private $availableLibraryHooks = array(
        'stream_wrapper' => 'Hoss\LibraryHooks\StreamWrapperHook',
        'curl' => 'Hoss\LibraryHooks\CurlHook',
        'soap' => 'Hoss\LibraryHooks\SoapHook',
    );

    /**
     * Returns a list of enabled LibraryHook class names.
     *
     * Only class names are returned, any object creation happens
     * in the VCRFactory.
     *
     * @return string[] List of LibraryHook class names.
     */
    public function getLibraryHooks()
    {
        return $this->availableLibraryHooks;
    }
}

?>
