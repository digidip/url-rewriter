<?php

namespace digidip\Adapters;

use digidip\Modules\Filesystem\StandardFileReader;
use digidip\Modules\Filesystem\StandardFileWriter;

class FilePathCircuitBreakerAdapter extends FileCircuitBreakerAdapter
{
    public function __construct(string $path)
    {
        parent::__construct(new StandardFileReader($path), new StandardFileWriter($path));
    }
}
