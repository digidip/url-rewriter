<?php

namespace digidip;

use digidip\Contracts\CircuitBreakerAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CircuitBreaker
{
    const OPTION_TIMEOUT = 'timeout';
    const OPTION_TIME_WINDOW = 'timeWindow';
    const OPTION_FAILURE_THRESHOLD = 'failureThreshold';
    const OPTION_OPENED_SAMPLE_RATE = 'openedSampleRate';
    const OPTION_CLOSED_SAMPLE_RATE = 'closedSampleRate';
    const OPTION_TESTING_SUCCESS_TIMESTAMP = 'testingSuccessTimestamp';
    const OPTION_TESTING_FAILURE_TIMESTAMP = 'testingSuccessTimestamp';

    const DEFAULT_OPTIONS = [
        self::OPTION_TIMEOUT => 1000, // number of milliseconds to wait before the HTTP connection times out.
        self::OPTION_TIME_WINDOW => 10, // time window in seconds, if the number of failures goes beyound `failureThresold` within this specified time window, the circuit will be opened.
        self::OPTION_FAILURE_THRESHOLD => 5, // number of failure events before opening circuit.
        self::OPTION_OPENED_SAMPLE_RATE => 1, // number of seconds before testing service while the circuit is open.
        self::OPTION_CLOSED_SAMPLE_RATE => 5, // number of seconds before testing service while the circuit is closed.

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

    public function __construct(
        CircuitBreakerAdapter $adapter,
        ?array $options = [],
        ?string $url = null,
        ?Client $client = null
    ) {
        $this->adapter = $adapter;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->url = $url ?? CircuitBreakerAdapter::DIGIDIP_CHECK_URL;
        $this->client = $client ?? new Client();

        $this->initialiseAdapter();
    }

    public function evaluateHealth(): void
    {
        $now = (new \DateTime())->getTimestamp() - $this->adapter->getSampleRate();
        $last = $this->adapter->getLastSampleTimestamp();

        // var_dump("{$now} > {$last}");
        if ($last === null || $now > $last) {
            try {
                $response = $this->client->head($this->url, [
                    'timeout' => $this->options[self::OPTION_TIMEOUT] / 1000,
                ]);

                if ($response->getStatusCode() === 200) {
                    $this->adapter->registerSuccess($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_SUCCESS_TIMESTAMP]);
                    if ($this->adapter->getFailureCount() === 0) {
                        $this->adapter->setSampleRate($this->options[self::OPTION_CLOSED_SAMPLE_RATE]);
                    }
                } else {
                    $this->adapter->registerFailure($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                    $this->adapter->setSampleRate($this->options[self::OPTION_OPENED_SAMPLE_RATE]);
                    $this->adapter->setError("HTTP Response contained Status Code: {$response->getStatusCode()}");
                }
            } catch (RequestException $e) {
                $this->adapter->registerFailure($this->options[self::OPTION_FAILURE_THRESHOLD], $this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                $this->adapter->setSampleRate($this->options[self::OPTION_OPENED_SAMPLE_RATE]);
                $this->adapter->setError($e->getMessage());
            } catch (\Throwable $e) {
                // $this->logger->critical()
                $this->adapter->registerFailure($this->options[self::OPTION_TESTING_FAILURE_TIMESTAMP]);
                $this->adapter->setSampleRate($this->options[self::OPTION_OPENED_SAMPLE_RATE]);
                $this->adapter->setError((string) $e);
            }
        }

        $this->inspectCircuit();
    }

    public function isOpen(): bool
    {
        return $this->adapter->getCircuitState();
    }

    protected function inspectCircuit(): void
    {
        $lastFailure = $this->adapter->getLastFailureTimestamp();
        if ($lastFailure === null) {
            $this->adapter->persist();
            return; // service is operational.
        }

        $cutOff = (new \DateTime())->getTimestamp() - $this->options[self::OPTION_TIME_WINDOW];
        if ($lastFailure !== null && $this->adapter->getFailureCount() === 0) { //$cutOff > $lastFailure) {
            // no new errors within the last sample window
            $this->adapter->setCircuitToClosed();
            $this->adapter->clearFailures();
            $this->adapter->persist();
            return;
        }

        if ($lastFailure !== null && $cutOff < $lastFailure && $this->adapter->getFailureCount() >= $this->options[self::OPTION_FAILURE_THRESHOLD]) {
            // To many failures have been observed
            $this->adapter->setCircuitToOpen();
            $this->adapter->persist();
            return;
        }

        $this->adapter->persist();
    }

    protected function initialiseAdapter(): void
    {
        if ($this->adapter->initialise() === false) {
            // need to update and persist a new configure, since no previous data exists for circuit breaker.
            $this->adapter->setSampleRate($this->options[self::OPTION_CLOSED_SAMPLE_RATE]);
            $this->adapter->setCircuitToClosed();
            $this->adapter->persist();
        }
    }
}
