<?php

namespace digidip\Adapters;

class RedisCircuitBreakerAdapter extends BaseCircuitBreakerAdapter
{
    const REDIS_CACHE_KEY = 'digidip/RedisCircuitBreakerAdpater';

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $redisCacheKey;

    public function __construct(\Redis $redis, string $cachekey = self::REDIS_CACHE_KEY)
    {
        $this->redis = $redis;
        $this->redisCacheKey = $cachekey;
    }

    function persist(): void
    {
        $this->redis->set($this->redisCacheKey, json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->redis->get($this->redisCacheKey)) {

            // @todo file system error management improvement here, make sure we prevent the app from crashing (with options).

            // create new file
            $this->redis->set($this->redisCacheKey, json_encode($this->payload()));
            return false;
        }

        $data = json_decode($this->redis->get($this->redisCacheKey), true);
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
