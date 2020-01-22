<?php

namespace digidip\Modules\Filesystem\Contracts;

interface FileReader {
    function read(): string;
    function exists(): bool;
}