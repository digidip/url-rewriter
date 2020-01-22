<?php

namespace digidip\Adapters;

use digidip\Loggers\NullLogger;
use digidip\Modules\Filesystem\Contracts\FileReader;
use digidip\Modules\Filesystem\Contracts\FileWriter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class FileCircuitBreakerAdapter extends BaseCircuitBreakerAdapter implements LoggerAwareInterface
{
    /**
     * @var FileReader
     */
    private $reader;

    /**
     * @var FileWriter
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    // @todo file reader & writer injection
    public function __construct(FileReader $reader, FileWriter $writer, ?LoggerInterface $logger = null)
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    function persist(): void
    {
        $this->logger->debug("FileCircuitBreakerAdapter::persist() Called");
        $this->writer->write(json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->reader->exists()) {
            $this->logger->debug("FileCircuitBreakerAdapter::initialise() No existing file found.");

            if (!$this->writer->isWritable()) {
                $this->logger->critical("No write access for file path: {$this->writer->path()}");
                throw new \Exception("No write access for file path: {$this->writer->path()}");
            }

            // create new file
            $this->writer->write(json_encode($this->payload()));
            return false;
        }

        if (!$this->reader->isReadable()) {
            $this->logger->critical("No read access for file path: {$this->writer->path()}");
            throw new \Exception("No read access for file path: {$this->writer->path()}");
        }

        $this->logger->debug("FileCircuitBreakerAdapter::initialise() File found, reading state into memory.");
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
