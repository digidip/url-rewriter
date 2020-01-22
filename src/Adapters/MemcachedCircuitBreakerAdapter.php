<?php

namespace digidip\Adapters;

class MemcachedCircuitBreakerAdapter extends BaseCircuitBreakerAdapter
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

    public function __construct(\Memcached $memcache, string $cachekey = self::MEMCACHED_CACHE_KEY)
    {
        $this->memcache = $memcache;
        $this->memcachedCacheKey = $cachekey;
    }

    function persist(): void
    {
        $this->memcache->set($this->memcachedCacheKey, json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->memcache->get($this->memcachedCacheKey)) {

            // @todo file system error management improvement here, make sure we prevent the app from crashing (with options).

            // create new file
            $this->memcache->set($this->memcachedCacheKey, json_encode($this->payload()));
            return false;
        }

        $data = json_decode($this->memcache->get($this->memcachedCacheKey), true);
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
