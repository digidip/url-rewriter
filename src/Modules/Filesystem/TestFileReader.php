<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileReader;

class TestFileReader implements FileReader
{
    private $data;

    public function __construct(string &$data)
    {
        $this->data = &$data;
    }

    public function read(): string
    {
        return $this->data;
    }

    function exists(): bool
    {
        return mb_strlen($this->data) > 0;
    }

    public function path(): string
    {
        return 'testing - N/A';
    }

    function isReadable(): bool
    {
        return true;
    }
}
