<?php

namespace digidip\Adapters;

use digidip\Loggers\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class MemcachedCircuitBreakerAdapter extends BaseCircuitBreakerAdapter implements LoggerAwareInterface
{
    const MEMCACHED_CACHE_KEY = 'digidip/MemcachedCircuitBreakerAdpater';

    /**
     * @var \Memcache
     */
    private $memcache;

    /**
     * @var string
     */
    private $memcachedCacheKey;

    public function __construct(\Memcached $memcache, string $cachekey = self::MEMCACHED_CACHE_KEY, ?LoggerInterface $logger = null)
    {
        $this->memcache = $memcache;
        $this->memcachedCacheKey = $cachekey;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    function persist(): void
    {
        $this->logger->debug("MemcachedCircuitBreakerAdapter::persist() Called");
        $this->memcache->set($this->memcachedCacheKey, json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->memcache->get($this->memcachedCacheKey)) {
            $this->logger->debug("MemcachedCircuitBreakerAdapter::initialise() No existing storage found.");
            $this->memcache->set($this->memcachedCacheKey, json_encode($this->payload()));
            return false;
        }

        $this->logger->debug("MemcachedCircuitBreakerAdapter::initialise() Storage found, reading state into memory.");
        $this->load();

        return true;
    }

    public function load(): void {
        $data = json_decode($this->memcache->get($this->memcachedCacheKey), true);
        if ($data) {
            $this->isOpen = $data['isOpen'] ?? $this->isOpen;
            $this->lastError = $data['lastError'] ?? $this->lastError;
            $this->failureCount = $data['failureCount'] ?? $this->failureCount;
            $this->lastFailureTimestamp = $data['lastFailureTimestamp'] ?? $this->lastFailureTimestamp;
            $this->lastSampleTimestamp = $data['lastSampleTimestamp'] ?? $this->lastSampleTimestamp;
            $this->sampleRate = $data['sampleRate'] ?? $this->sampleRate;
        }
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
