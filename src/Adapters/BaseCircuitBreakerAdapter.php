<?php

namespace digidip\Adapters;

use digidip\Contracts\CircuitBreakerAdapter;

abstract class BaseCircuitBreakerAdapter implements CircuitBreakerAdapter {
    /**
     * @var boolean|null
     */
    protected $isOpen = null;

    /**
     * @var string
     */
    protected $lastError = null;

    /**
     * @var int     epoch timestamp of the last observed failure
     */
    protected $lastFailureTimestamp = null;

    /**
     * @var int     number of observed failures
     */
    protected $failureCount = 0;

    /**
     * @var int|null    the last timestamp fr0k when the service was last checked
     */
    protected $lastSampleTimestamp = null;

    /**
     * @var int     the current amount of seconds between requesting the state of the service
     */
    protected $sampleRate;

    function getCircuitState(): bool
    {
        if ($this->isOpen === null) {
            $class = get_class($this);
            throw new \RuntimeException("{$class}::getCircuitState() was called prior to {$class}::setCircuitToOpen() or {$class}::setCircuitToClosed() being called.");
        }

        return $this->isOpen;
    }

    function setCircuitToOpen(): void
    {
        $this->isOpen = true;
    }

    function setCircuitToClosed(): void
    {
        $this->isOpen = false;
    }

    function registerFailure(int $maxFailureCount, ?\DateTime $timestamp = null): void
    {
        $this->failureCount = min($this->failureCount + 1, $maxFailureCount);
        $this->lastFailureTimestamp = $this->lastSampleTimestamp = ($timestamp ?? new \DateTime())->getTimestamp();
    }

    function registerSuccess(int $maxFailureCount, ?\DateTime $timestamp = null): void
    {
        $this->failureCount = ($this->failureCount > 0) ? $this->failureCount - 1 : min($this->failureCount, $maxFailureCount);
        $this->lastSampleTimestamp = ($timestamp ?? new \DateTime())->getTimestamp();
    }

    function setError(?string $error): void
    {
        $this->lastError = $error;
    }

    function getFailureCount(): int
    {
        return $this->failureCount;
    }

    function clearFailures(): void
    {
        $this->failureCount = 0;
        $this->lastFailureTimestamp = null;
        $this->lastError = null;
    }

    function setSampleRate(int $sampleRate): void
    {
        $this->sampleRate = $sampleRate;
    }

    function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    function getLastFailureTimestamp(): ?int
    {
        return $this->lastFailureTimestamp;
    }

    function getLastSampleTimestamp(): ?int
    {
        return $this->lastSampleTimestamp;
    }

    abstract function persist(): void;
    abstract function initialise(): bool;
}