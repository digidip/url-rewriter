<?php

use digidip\Adapters\MemcachedCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Strategies\DigidipRewriterStrategy;
use digidip\UrlRewriter;

$memcached = new \Memcached();
$memcached->addServer('127.0.0.1', 11211);

$adapter = new MemcachedCircuitBreakerAdapter($memcached);
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlRewriter($circuit, new DigidipRewriterStrategy(12345));

$url = $rewriter->getUrl('http://www.merchant.com', []);
