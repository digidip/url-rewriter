<?php

namespace digidip\Modules\Filesystem\Contracts;

interface FileWriter {
    function write(string $content): void;
    function path(): string;
    function isWritable(): bool;
}