<?php

namespace digidip;

use digidip\Contracts\CircuitBreakerAdapter;
use digidip\Loggers\NullLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class CircuitBreaker implements LoggerAwareInterface
{
    const OPTION_TIMEOUT = 'timeout';
    const OPTION_FAILURE_THRESHOLD = 'failureThreshold';
    const OPTION_OPENED_SAMPLE_RATE = 'openedSampleRate';
    const OPTION_CLOSED_SAMPLE_RATE = 'closedSampleRate';
    const OPTION_TESTING_SUCCESS_TIMESTAMP = 'testingSuccessTimestamp';
    const OPTION_TESTING_FAILURE_TIMESTAMP = 'testingSuccessTimestamp';

    const DEFAULT_OPTIONS = [
        self::OPTION_TIMEOUT => 1000, // number of milliseconds to wait before the HTTP connection times out.
        self::OPTION_FAILURE_THRESHOLD => 5, // number of failure events before opening circuit.
        self::OPTION_OPENED_SAMPLE_RATE => 1, // number of seconds before testing service while the circuit is producing failures.
        self::OPTION_CLOSED_SAMPLE_RATE => 5, // number of seconds before testing service while the circuit is in a healthy state (failure count === 0)

        // Testing options
        self::OPTION_TESTING_SUCCESS_TIMESTAMP => null,
        self::OPTION_TESTING_FAILURE_TIMESTAMP => null,
    ];

    /**
     * @var digidip\Contracts\CircuitBreakerAdapter
     */
    private $adapter;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CircuitBreakerAdapter $adapter,
        ?array $options = [],
        ?string $url = null,
        ?Client $client = null,
        ?LoggerInterface $logger = null
    ) {
        $this->adapter = $adapter;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->url = $url ?? CircuitBreakerAdapter::DIGIDIP_CHECK_URL;
        $this->client = $client ?? new Client();
        $this->logger = $logger ?? new NullLogger();
        $this->logger->debug("CircuitBreaker::__construct() called.");

        $this->initialiseAdapter();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        if (class_implements($this->adapter, LoggerAwareInterface::class)) {
            $this->adapter->setLogger($this->logger);
        }
    }

    public function evaluateHealth(): void
    {
        $this->logger->debug("CircuitBreaker::evaluateHealth() called.");
        $this->adapter->load();

        $now = (new \DateTime())->getTimestamp() - $this->adapter->getSampleRate();
        $last = $this->adapter->getLastSampleTimestamp();

        $this->logger->debug("CircuitBreaker::evaluateHealth(): last sampled timestamp '{$last}', current timestamp (minus sampleRate) '{$now}'.");
        if ($last === null || $now > $last) {
            try {
                $response = $this->client->head($this->url, [
                    'timeout' => $this->options[self::OPTION_TIMEOUT] / 1000,
                ]);
                $this->logger->debug("CircuitBreaker::evaluateHealth(): HTTP Request called with status code response: {$response->getStatusCode()}");

                if ($response->getStatusCode() === 200) {
                    $this->adapter->registerSuccess($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_SUCCESS_TIMESTAMP]);
                    $this->logger->debug("CircuitBreaker::evaluateHealth(): Registered successful response with adapter");

                    if ($this->adapter->getFailureCount() === 0) {
                        $this->adapter->setSampleRate($this->options[self::OPTION_CLOSED_SAMPLE_RATE]);
                        $this->logger->debug("CircuitBreaker::evaluateHealth(): Called reset sample rate to '{$this->options[self::OPTION_CLOSED_SAMPLE_RATE]}' in adapter");
                    }
                } else {
                    $this->adapter->registerFailure($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                    $this->logger->debug("CircuitBreaker::evaluateHealth(): Registered (Non 200 HTTP Status) failure response with adapter");

                    $this->updateOpenSampleRate();
                    $this->adapter->setError("HTTP Response contained Status Code: {$response->getStatusCode()}");
                }
            } catch (RequestException $e) {
                $this->adapter->registerFailure($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                $this->logger->debug("CircuitBreaker::evaluateHealth(): Registered (Request Exception from Client) failure response with adapter");

                $this->updateOpenSampleRate();
                $this->adapter->setError($e->getMessage());
            } catch (\Throwable $e) {
                $this->logger->critical((string) $e, [$e]);

                $this->adapter->registerFailure($this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                $this->logger->debug("CircuitBreaker::evaluateHealth(): Registered (Other Throwable error) failure response with adapter");

                $this->updateOpenSampleRate();
                $this->adapter->setError((string) $e);
            }
        }

        $this->inspectCircuit();
    }

    public function isOpen(): bool
    {
        $this->logger->debug("CircuitBreaker::isOpen() called.");
        return $this->adapter->getCircuitState();
    }

    protected function updateOpenSampleRate(): void
    {
        if ($this->options[self::OPTION_OPENED_SAMPLE_RATE] !== $this->adapter->getSampleRate()) {
            $this->adapter->setSampleRate($this->options[self::OPTION_OPENED_SAMPLE_RATE]);
            $this->logger->debug("CircuitBreaker::evaluateHealth(): Set sample rate to '{$this->options[self::OPTION_OPENED_SAMPLE_RATE]}' in adapter");
        }
    }

    protected function inspectCircuit(): void
    {
        $this->logger->debug("CircuitBreaker::inspectCircuit() Called.");

        $lastFailure = $this->adapter->getLastFailureTimestamp();
        if ($lastFailure === null) {
            $this->logger->debug("CircuitBreaker::inspectCircuit() No failures timestamps found.");
            $this->adapter->persist();
            return; // service is operational.
        }

        if ($lastFailure !== null && $this->adapter->getFailureCount() === 0) {
            $this->logger->debug("CircuitBreaker::inspectCircuit() Service has been restored to normal, closing circuit.");

            // no new errors within the last sample window
            $this->adapter->setCircuitToClosed();
            $this->adapter->clearFailures();
            $this->adapter->persist();
            return;
        }

        if ($lastFailure !== null && $this->adapter->getFailureCount() >= $this->options[self::OPTION_FAILURE_THRESHOLD]) {
            $this->logger->debug("CircuitBreaker::inspectCircuit() Detected service issues, opening circuit.");

            // To many failures have been observed
            $this->adapter->setCircuitToOpen();
            $this->adapter->persist();
            return;
        }

        $this->logger->debug("CircuitBreaker::inspectCircuit() No changes to circuit breaker state.");
        $this->adapter->persist();
    }

    protected function initialiseAdapter(): void
    {
        $this->logger->debug("CircuitBreaker::initialiseAdapter() Called.");

        if ($this->adapter->initialise() === false) {
            // need to update and persist a new configure, since no previous data exists for circuit breaker.
            $this->logger->debug("CircuitBreaker::initialiseAdapter() No previously persisted storage detected, will initialise adapter state.");

            $this->adapter->setSampleRate($this->options[self::OPTION_CLOSED_SAMPLE_RATE]);
            $this->adapter->setCircuitToClosed();
            $this->adapter->persist();
        }
    }
}
