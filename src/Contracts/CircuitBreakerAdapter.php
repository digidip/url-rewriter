<?php

namespace digidip\Contracts;

interface CircuitBreakerAdapter {
    public const DIGIDIP_CHECK_URL = 'https://visit.digidip.net/digi-health-check.php';

    function getCircuitState(): bool;
    function setCircuitToOpen(): void;
    function setCircuitToClosed(): void;

    function registerFailure(int $maxFailureCount, ?\DateTime $timestamp = null): void;
    function registerSuccess(int $maxFailureCount, ?\DateTime $timestamp = null): void;
    function setError(?string $error): void;
    function getFailureCount(): int;
    function clearFailures(): void;

    function setSampleRate(int $sampleRate): void;
    function getSampleRate(): int;

    function getLastFailureTimestamp(): ?int;
    function getLastSampleTimestamp(): ?int;

    function persist(): void;
    function load(): void;
    function initialise(): bool;
}