<?php

namespace digidip\Modules\Filesystem;

use digidip\Modules\Filesystem\Contracts\FileWriter;

class StandardFileWriter implements FileWriter {
    /**
     * @var strinf
     */
    private $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    function writer(string $content): void {
        file_put_contents($this->path, $content);
    }
}