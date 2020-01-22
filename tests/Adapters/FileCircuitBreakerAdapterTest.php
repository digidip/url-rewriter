<?php

use PHPUnit\Framework\TestCase;
use digidip\Adapters\FileCircuitBreakerAdapter;
use digidip\Modules\Filesystem\Contracts\FileReader;
use digidip\Modules\Filesystem\Contracts\FileWriter;
use digidip\Modules\Filesystem\TestFileReader;
use digidip\Modules\Filesystem\TestFileWriter;

/**
 * FileCircuitBreakerAdapterTest
 * @group Adapters
 */
class FileCircuitBreakerAdapterTest extends TestCase
{
    /**
     * @test
     */
    public function test_withoutInitialisingAdapter()
    {
        $this->expectExceptionMessage('digidip\Adapters\FileCircuitBreakerAdapter::getCircuitState() was called prior to digidip\Adapters\FileCircuitBreakerAdapter::setCircuitToOpen() or digidip\Adapters\FileCircuitBreakerAdapter::setCircuitToClosed() being called.');

        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);
        $adapter->getCircuitState();
    }

    /**
     * @test
     */
    public function test_withInitialisingAdapter()
    {
        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);

        $adapter->setSampleRate(5);
        $adapter->setCircuitToClosed();
        $this->assertEquals(false, $adapter->getCircuitState());

        $adapter->setCircuitToOpen();
        $this->assertEquals(true, $adapter->getCircuitState());
        $this->assertEquals('', $buffer);

        $adapter->persist();
        $this->assertEquals('{"isOpen":true,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}', $buffer);

        $adapter->setCircuitToClosed();
        $adapter->persist();
        $this->assertEquals('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}', $buffer);
    }

    /**
     * @test
     */
    public function test_countersAndDates()
    {
        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);

        $stub = $this->createMock(\DateTime::class);
        $stub->method('getTimestamp')->willReturn(10000);

        $now = new DateTime();
        $adapter->setSampleRate(1);
        $adapter->registerFailure(3, $now);
        $adapter->setError('Test error');
        $adapter->persist();
        $this->assertEquals($now->getTimestamp(), $adapter->getLastSampleTimestamp());
        $this->assertEquals($now->getTimestamp(), $adapter->getLastFailureTimestamp());
        $this->assertEquals('{"isOpen":null,"lastError":"Test error","failureCount":1,"lastFailureTimestamp":'. $now->getTimestamp() .',"lastSampleTimestamp":'. $now->getTimestamp() .',"sampleRate":1}', $buffer);

        $now1 = (new DateTime())->modify('+5 seconds');
        $adapter->setSampleRate(5);
        $adapter->registerSuccess(3, $now1);
        $adapter->persist();
        $this->assertEquals('{"isOpen":null,"lastError":"Test error","failureCount":0,"lastFailureTimestamp":'. $now->getTimestamp() .',"lastSampleTimestamp":'. $now1->getTimestamp() .',"sampleRate":5}', $buffer);

        $adapter->clearFailures();
        $adapter->persist();
        $this->assertEquals('{"isOpen":null,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":'. $now1->getTimestamp() .',"sampleRate":5}', $buffer);
        $this->assertEquals($now1->getTimestamp(), $adapter->getLastSampleTimestamp());
    }
}
