<?php

use PHPUnit\Framework\TestCase;
use digidip\Adapters\MemcachedCircuitBreakerAdapter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * MemcacheCircuitBreakerAdapterTest
 * @group Adapters
 */
class MemcachedCircuitBreakerAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    const TESTING_CACHE_KEY = 'digidip/MemcachedCircuitBreakerAdapterTest';

    /**
     * @test
     */
    public function test_withoutInitialisingAdapter()
    {
        $this->expectExceptionMessage('digidip\Adapters\MemcachedCircuitBreakerAdapter::getCircuitState() was called prior to digidip\Adapters\MemcachedCircuitBreakerAdapter::setCircuitToOpen() or digidip\Adapters\MemcachedCircuitBreakerAdapter::setCircuitToClosed() being called.');

        $memcached = Mockery::mock(\Memcached::class);
        $adapter = new MemcachedCircuitBreakerAdapter($memcached, self::TESTING_CACHE_KEY);
        $adapter->getCircuitState();
    }


    /**
     * @test
     */
    public function test_withInitialisingWithoutDataInStore()
    {
        $mock = Mockery::Mock(\Memcached::class);
        $adapter = new MemcachedCircuitBreakerAdapter($mock, self::TESTING_CACHE_KEY);
        $mock->shouldReceive('get')->once()->with(self::TESTING_CACHE_KEY)->andReturn(false);
        $mock->shouldReceive('set')->once()->with(self::TESTING_CACHE_KEY, '{"isOpen":null,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":null}');

        $this->assertFalse($adapter->initialise());
    }

    /**
     * @test
     */
    public function test_withInitialisingWithDataInStore()
    {
        $mock = Mockery::Mock(\Memcached::class);
        $adapter = new MemcachedCircuitBreakerAdapter($mock, self::TESTING_CACHE_KEY);
        $mock->shouldReceive('get')->times(2)->with(self::TESTING_CACHE_KEY)->andReturn('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}');
        $mock->shouldNotReceive('set');

        $this->assertTrue($adapter->initialise());
        $this->assertEquals(5, $adapter->getSampleRate());
        $this->assertFalse($adapter->getCircuitState());
    }

    /**
     * @test
     */
    public function test_withInitialisingAdapter()
    {
        $memcached = Mockery::mock(\Memcached::class);
        $adapter = new MemcachedCircuitBreakerAdapter($memcached, self::TESTING_CACHE_KEY);

        $adapter->setSampleRate(5);
        $adapter->setCircuitToClosed();
        $this->assertEquals(false, $adapter->getCircuitState());

        $adapter->setCircuitToOpen();
        $this->assertEquals(true, $adapter->getCircuitState());
        $memcached->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":true,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}'
        );
        $adapter->persist();

        $memcached->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}'
        );
        $adapter->setCircuitToClosed();
        $adapter->persist();
    }

    /**
     * @test
     */
    public function test_countersAndDates()
    {
        $memcached = Mockery::mock(\Memcached::class);
        $adapter = new MemcachedCircuitBreakerAdapter($memcached, self::TESTING_CACHE_KEY);

        $stub = $this->createMock(\DateTime::class);

        $stub->method('getTimestamp')->willReturn(10000);
        $now = new DateTime();
        $adapter->setSampleRate(1);
        $adapter->registerFailure(3, $now);
        $adapter->setError('Test error');

        $memcached->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":"Test error","failureCount":1,"lastFailureTimestamp":' . $now->getTimestamp() . ',"lastSampleTimestamp":' . $now->getTimestamp() . ',"sampleRate":1}'
        );
        $adapter->persist();
        $this->assertEquals($now->getTimestamp(), $adapter->getLastSampleTimestamp());
        $this->assertEquals($now->getTimestamp(), $adapter->getLastFailureTimestamp());

        $now1 = (new DateTime())->modify('+5 seconds');
        $adapter->setSampleRate(5);
        $adapter->registerSuccess(3, $now1);

        $memcached->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":"Test error","failureCount":0,"lastFailureTimestamp":' . $now->getTimestamp() . ',"lastSampleTimestamp":' . $now1->getTimestamp() . ',"sampleRate":5}'
        );
        $adapter->persist();

        $adapter->clearFailures();
        $memcached->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":' . $now1->getTimestamp() . ',"sampleRate":5}'
        );
        $adapter->persist();
        $this->assertEquals($now1->getTimestamp(), $adapter->getLastSampleTimestamp());
    }
}
