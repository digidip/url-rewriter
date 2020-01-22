<?php

use digidip\Adapters\FileCircuitBreakerAdapter;
use PHPUnit\Framework\TestCase;
use digidip\CircuitBreaker;
use digidip\Modules\Filesystem\TestFileReader;
use digidip\Modules\Filesystem\TestFileWriter;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * CircuitBreakerTest
 * @group digidip
 */
class CircuitBreakerTest extends TestCase
{
    /**
     * @test
     */
    public function test_normalInstantitation()
    {
        // mock guzzlehttp client
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handleStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handleStack]);

        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);

        $circuitBreaker = new CircuitBreaker(
            $adapter,
            [
                CircuitBreaker::OPTION_CLOSED_SAMPLE_RATE => 3,
                CircuitBreaker::OPTION_TESTING_SUCCESS_TIMESTAMP => $timestamp = new DateTime(),
            ],
            null,
            $client
        );
        $this->assertEquals(false, $circuitBreaker->isOpen());
        $this->assertEquals(3, $adapter->getSampleRate());

        $circuitBreaker->evaluateHealth();
        $this->assertEquals(0, $adapter->getFailureCount());
        $this->assertEquals(3, $adapter->getSampleRate());
        $this->assertEquals($timestamp->getTimestamp(), $adapter->getLastSampleTimestamp());
        $this->assertEquals('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":3}', $buffer);
    }

    /**
     * @test
     */
    public function test_openingAndClosingOfCircuit()
    {
        // mock guzzlehttp client
        $mock = new MockHandler([
            new Response(404),
            new Response(404),
            new Response(404),
            new Response(404),

            new Response(200),
            new Response(200),
            new Response(200),
            new Response(200),

            new ConnectException('Timeout failure', new Request('HEAD', 'test')),
            new ConnectException('Timeout failure', new Request('HEAD', 'test')),
            new ConnectException('Timeout failure', new Request('HEAD', 'test')),
            new ConnectException('Timeout failure', new Request('HEAD', 'test')),
        ]);
        $handleStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handleStack]);

        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);

        $timestamp = (new DateTime())->modify('-5 seconds');
        $circuitBreaker = new CircuitBreaker(
            $adapter,
            [
                CircuitBreaker::OPTION_CLOSED_SAMPLE_RATE => 3,
                CircuitBreaker::OPTION_FAILURE_THRESHOLD => 3,
                CircuitBreaker::OPTION_TESTING_SUCCESS_TIMESTAMP => &$timestamp,
                CircuitBreaker::OPTION_TESTING_FAILURE_TIMESTAMP => &$timestamp,
            ],
            null,
            $client
        );

        // Open Circuit
        $circuitBreaker->evaluateHealth();

        $timestamp = $timestamp->modify('-2 seconds');
        $circuitBreaker->evaluateHealth();

        $timestamp = $timestamp->modify('-2 seconds');
        $circuitBreaker->evaluateHealth();

        $timestamp = $timestamp->modify('-2 seconds');
        $circuitBreaker->evaluateHealth();

        $this->assertEquals(3, $adapter->getFailureCount());
        $this->assertEquals('{"isOpen":true,"lastError":"Client error: `HEAD https:\/\/visit.digidip.net\/digi-health-check.php` resulted in a `404 Not Found` response","failureCount":3,"lastFailureTimestamp":' . $timestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);

        // Close circuit
        $lastFailureTimestamp = clone $timestamp;
        $timestamp = $timestamp->modify('-3 seconds');
        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":true,"lastError":"Client error: `HEAD https:\/\/visit.digidip.net\/digi-health-check.php` resulted in a `404 Not Found` response","failureCount":2,"lastFailureTimestamp":' . $lastFailureTimestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);

        $timestamp = $timestamp->modify('-3 seconds');
        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":true,"lastError":"Client error: `HEAD https:\/\/visit.digidip.net\/digi-health-check.php` resulted in a `404 Not Found` response","failureCount":1,"lastFailureTimestamp":' . $lastFailureTimestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);

        $timestamp = $timestamp->modify('-3 seconds');
        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":3}', $buffer);

        $timestamp = $timestamp->modify('-3 seconds');
        $circuitBreaker->evaluateHealth();

        $this->assertEquals(0, $adapter->getFailureCount());
        $this->assertEquals('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":3}', $buffer);

        // Open circuit due to network issues.
        $timestamp = (new DateTime())->modify('-3 seconds');

        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":false,"lastError":"Timeout failure","failureCount":1,"lastFailureTimestamp":' . $timestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);
        $timestamp = $timestamp->modify('-1 seconds');

        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":false,"lastError":"Timeout failure","failureCount":2,"lastFailureTimestamp":' . $timestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);
        $timestamp = $timestamp->modify('-1 seconds');

        $circuitBreaker->evaluateHealth();
        $this->assertEquals('{"isOpen":true,"lastError":"Timeout failure","failureCount":3,"lastFailureTimestamp":' . $timestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);
        $timestamp = $timestamp->modify('-1 seconds');

        $circuitBreaker->evaluateHealth();
        $this->assertEquals(3, $adapter->getFailureCount());
        $this->assertEquals('{"isOpen":true,"lastError":"Timeout failure","failureCount":3,"lastFailureTimestamp":' . $timestamp->getTimestamp() . ',"lastSampleTimestamp":' . $timestamp->getTimestamp() . ',"sampleRate":1}', $buffer);
    }
}
