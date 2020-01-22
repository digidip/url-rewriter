<?php

namespace digidip\Adapters;

use digidip\Modules\Filesystem\StandardFileReader;
use digidip\Modules\Filesystem\StandardFileWriter;
use Psr\Log\LoggerInterface;

class FilePathCircuitBreakerAdapter extends FileCircuitBreakerAdapter
{
    public function __construct(string $path, ?LoggerInterface $logger = null)
    {
        parent::__construct(new StandardFileReader($path), new StandardFileWriter($path), $logger);
    }
}
