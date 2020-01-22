<?php

use PHPUnit\Framework\TestCase;
use digidip\Adapters\RedisCircuitBreakerAdapter;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * MemcacheCircuitBreakerAdapterTest
 * @group Adapters
 *
 * @Note Important notices, that currently PHPUnit has no way of mocking \Memcached, so we must
 * use a localhost service to perform the tests. This is totally undesirable and unfortuantely
 * is the only solution that currently exists AFAIK.
 *
 * The primary reasoning is Memcached provides no Reflection API as part of the extension.
 */
class RedisCircuitBreakerAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    const TESTING_CACHE_KEY = 'digidip/RedisCircuitBreakerAdapterTest';

    /**
     * @test
     */
    public function test_withoutInitialisingAdapter()
    {
        $this->expectExceptionMessage('digidip\Adapters\RedisCircuitBreakerAdapter::getCircuitState() was called prior to digidip\Adapters\RedisCircuitBreakerAdapter::setCircuitToOpen() or digidip\Adapters\RedisCircuitBreakerAdapter::setCircuitToClosed() being called.');

        $mock = Mockery::Mock(\Redis::class);
        $adapter = new RedisCircuitBreakerAdapter($mock, self::TESTING_CACHE_KEY);
        $adapter->getCircuitState();
    }

    /**
     * @test
     */
    public function test_withInitialisingWithoutDataInStore()
    {
        $mock = Mockery::Mock(\Redis::class);
        $adapter = new RedisCircuitBreakerAdapter($mock, self::TESTING_CACHE_KEY);
        $mock->shouldReceive('get')->once()->with(self::TESTING_CACHE_KEY)->andReturn(false);
        $mock->shouldReceive('set')->once()->with(self::TESTING_CACHE_KEY, '{"isOpen":null,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":null}');

        $this->assertFalse($adapter->initialise());
    }

    /**
     * @test
     */
    public function test_withInitialisingWithDataInStore()
    {
        $mock = Mockery::Mock(\Redis::class);
        $adapter = new RedisCircuitBreakerAdapter($mock, self::TESTING_CACHE_KEY);
        $mock->shouldReceive('get')->times(2)->with(self::TESTING_CACHE_KEY)->andReturn('{"isOpen":false,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}');
        $mock->shouldNotReceive('set');

        $this->assertTrue($adapter->initialise());
        $this->assertEquals(5, $adapter->getSampleRate());
        $this->assertFalse($adapter->getCircuitState());
    }

    /**
     * @test
     */
    public function test_withPersistingAdapter()
    {
        $redis = Mockery::Mock(\Redis::class);
        $adapter = new RedisCircuitBreakerAdapter($redis, self::TESTING_CACHE_KEY);

        $adapter->setSampleRate(5);
        $adapter->setCircuitToClosed();
        $this->assertEquals(false, $adapter->getCircuitState());

        $adapter->setCircuitToOpen();
        $this->assertEquals(true, $adapter->getCircuitState());

        $redis->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":true,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":null,"sampleRate":5}'
        );
        $adapter->persist();

        $redis->shouldReceive('set')->once()->with(
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
        $redis = Mockery::Mock(\Redis::class);
        $adapter = new RedisCircuitBreakerAdapter($redis, self::TESTING_CACHE_KEY);

        $stub = Mockery::mock(\DateTime::class);
        $stub->shouldReceive('getTimestamp')->andReturn(10000);

        $now = new DateTime();
        $adapter->setSampleRate(1);
        $adapter->registerFailure(3, $now);
        $adapter->setError('Test error');

        $redis->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":"Test error","failureCount":1,"lastFailureTimestamp":'. $now->getTimestamp() .',"lastSampleTimestamp":'. $now->getTimestamp() .',"sampleRate":1}'
        );
        $adapter->persist();
        $this->assertEquals($now->getTimestamp(), $adapter->getLastSampleTimestamp());
        $this->assertEquals($now->getTimestamp(), $adapter->getLastFailureTimestamp());

        $now1 = (new DateTime())->modify('+5 seconds');
        $adapter->setSampleRate(5);
        $adapter->registerSuccess(3, $now1);

        $redis->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":"Test error","failureCount":0,"lastFailureTimestamp":'. $now->getTimestamp() .',"lastSampleTimestamp":'. $now1->getTimestamp() .',"sampleRate":5}'
        );
        $adapter->persist();

        $adapter->clearFailures();
        $redis->shouldReceive('set')->once()->with(
            self::TESTING_CACHE_KEY,
            '{"isOpen":null,"lastError":null,"failureCount":0,"lastFailureTimestamp":null,"lastSampleTimestamp":'. $now1->getTimestamp() .',"sampleRate":5}'
        );
        $adapter->persist();
        $this->assertEquals($now1->getTimestamp(), $adapter->getLastSampleTimestamp());
    }
}
