<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileWriter;

class StandardFileWriter implements FileWriter
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    function write(string $content): void
    {
        file_put_contents($this->path, $content, LOCK_EX);
    }

    public function path(): string
    {
        return $this->path;
    }

    function isWritable(): bool
    {
        return is_writable(dirname($this->path));
    }
}
