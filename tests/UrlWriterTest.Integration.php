<?php

use digidip\Adapters\FileCircuitBreakerAdapter;
use PHPUnit\Framework\TestCase;
use digidip\CircuitBreaker;
use digidip\Modules\Filesystem\TestFileReader;
use digidip\Modules\Filesystem\TestFileWriter;
use digidip\Strategies\TemplateRewriterStrategy;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use digidip\UrlWriter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * CircuitBreakerTest
 * @group digidip
 */
class UrlWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @test
     */
    public function test_circutBreakingMechanismWithUrlWriteer()
    {
        // mock guzzlehttp client
        $mock = new MockHandler([
            new Response(200),

            new Response(404),
            new Response(404),
            new Response(404),
            new Response(404),

            new Response(200),
            new Response(200),
            new Response(200),
            new Response(200),

            new Response(404),
            new Response(404),
            new Response(404),
        ]);
        $handleStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handleStack]);

        $buffer = '';
        $reader = new TestFileReader($buffer);
        $writer = new TestFileWriter($buffer);
        $adapter = new FileCircuitBreakerAdapter($reader, $writer);

        $timestamp = (new DateTime())->modify('-5 seconds');;
        $circuitBreaker = new CircuitBreaker(
            $adapter,
            [
                CircuitBreaker::OPTION_CLOSED_SAMPLE_RATE => 3,
                CircuitBreaker::OPTION_FAILURE_THRESHOLD => 3,
                CircuitBreaker::OPTION_TESTING_SUCCESS_TIMESTAMP => &$timestamp,
                CircuitBreaker::OPTION_TESTING_FAILURE_TIMESTAMP => &$timestamp,
                CircuitBreaker::OPTION_TIME_WINDOW => 30,
            ],
            null,
            $client
        );

        $urlWriter = new UrlWriter(
            $circuitBreaker,
            new TemplateRewriterStrategy('http://localhost/visit?url={url}', [])
        );

        $timestamp = $timestamp->modify('-3 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-3 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-2 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://www.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://www.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());


        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://www.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());


        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://www.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
        // var_dump($buffer, $timestamp->getTimestamp());

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://localhost/visit?url=http%3A%2F%2Fwww.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));

        $timestamp = $timestamp->modify('-1 seconds');
        $this->assertEquals('http://www.mymerchant.com', $urlWriter->getUrl('http://www.mymerchant.com'));
    }
}
