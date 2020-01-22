<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileWriter;

class StandardFileWriter implements FileWriter {
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    function writer(string $content): void {
        file_put_contents($this->path, $content);
    }

    public function path(): string {
        return $this->path;
    }

    function isWritable(): bool
    {
        return is_writable($this->path);
    }
}