<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileWriter;

class TestFileWriter implements FileWriter
{
    private $data;

    public function __construct(string &$data)
    {
        $this->data = &$data;
    }

    public function write(string $content): void
    {
        $this->data = $content;
    }

    public function path(): string
    {
        return 'testing - N/A';
    }

    function isWritable(): bool
    {
        return true;
    }
}
