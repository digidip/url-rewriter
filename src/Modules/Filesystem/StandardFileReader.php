<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileReader;

class StandardFileReader implements FileReader
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    function read(): string
    {
        return file_get_contents($this->path);
    }

    function exists(): bool
    {
        return file_exists($this->path);
    }

    public function path(): string
    {
        return $this->path;
    }

    function isReadable(): bool
    {
        return is_readable($this->path);
    }
}
