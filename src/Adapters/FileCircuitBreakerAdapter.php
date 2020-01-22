<?php

namespace digidip\Adapters;

use digidip\Modules\Filesystem\Contracts\FileReader;
use digidip\Modules\Filesystem\Contracts\FileWriter;

class FileCircuitBreakerAdapter extends BaseCircuitBreakerAdapter
{
    /**
     * @var FileReader
     */
    private $reader;

    /**
     * @var FileWriter
     */
    private $writer;

    // @todo file reader & writer injection
    public function __construct(FileReader $reader, FileWriter $writer)
    {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    function persist(): void
    {
        $this->writer->write(json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->reader->exists()) {

            // @todo file system error management improvement here, make sure we prevent the app from crashing (with options).

            // create new file
            $this->writer->write(json_encode($this->payload()));
            return false;
        }

        $data = json_decode($this->reader->read(), true);
        $this->isOpen = $data['isOpen'] ?? $this->isOpen;
        $this->lastError = $data['lastError'] ?? $this->lastError;
        $this->failureCount = $data['failureCount'] ?? $this->failureCount;
        $this->lastFailureTimestamp = $data['lastFailureTimestamp'] ?? $this->lastFailureTimestamp;
        $this->lastSampleTimestamp = $data['lastSampleTimestamp'] ?? $this->lastSampleTimestamp;
        $this->sampleRate = $data['sampleRate'] ?? $this->defaultSampleRate;

        return true;
    }

    private function payload(): array {
        return [
            'isOpen' => $this->isOpen,
            'lastError' => $this->lastError,
            'failureCount' => $this->failureCount,
            'lastFailureTimestamp' => $this->lastFailureTimestamp,
            'lastSampleTimestamp' => $this->lastSampleTimestamp,
            'sampleRate' => $this->sampleRate,
        ];
    }
}
