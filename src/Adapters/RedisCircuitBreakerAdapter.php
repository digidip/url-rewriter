<?php

namespace digidip\Adapters;

use digidip\Loggers\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class RedisCircuitBreakerAdapter extends BaseCircuitBreakerAdapter implements LoggerAwareInterface
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

    public function __construct(\Redis $redis, string $cachekey = self::REDIS_CACHE_KEY, ?LoggerInterface $logger = null)
    {
        $this->redis = $redis;
        $this->redisCacheKey = $cachekey;
        $this->logger = $logger ?? new NullLogger();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    function persist(): void
    {
        $this->logger->debug("RedisCircuitBreakerAdapter::persist() Called");
        $this->redis->set($this->redisCacheKey, json_encode($this->payload()));
    }

    /**
     * @return bool     If file already exists, TRUE is returned. If a file is created new, FALSE will be returned.
     */
    public function initialise(): bool
    {
        if (!$this->redis->get($this->redisCacheKey)) {
            $this->logger->debug("RedisCircuitBreakerAdapter::initialise() No existing storage found.");
            $this->redis->set($this->redisCacheKey, json_encode($this->payload()));
            return false;
        }

        $this->logger->debug("RedisCircuitBreakerAdapter::initialise() Storage found, reading state into memory.");
        $this->load();

        return true;
    }

    public function load(): void
    {
        $data = json_decode($this->redis->get($this->redisCacheKey), true);
        if ($data) {
            $this->isOpen = $data['isOpen'] ?? $this->isOpen;
            $this->lastError = $data['lastError'] ?? $this->lastError;
            $this->failureCount = $data['failureCount'] ?? $this->failureCount;
            $this->lastFailureTimestamp = $data['lastFailureTimestamp'] ?? $this->lastFailureTimestamp;
            $this->lastSampleTimestamp = $data['lastSampleTimestamp'] ?? $this->lastSampleTimestamp;
            $this->sampleRate = $data['sampleRate'] ?? $this->sampleRate;
        }
    }

    private function payload(): array
    {
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
