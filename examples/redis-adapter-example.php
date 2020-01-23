<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use digidip\Adapters\RedisCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Strategies\DigidipRewriterStrategy;
use digidip\UrlRewriter;

$redis = new \Redis();
$redis->connect('localhost');

$adapter = new RedisCircuitBreakerAdapter($redis);
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlRewriter($circuit, new DigidipRewriterStrategy(12345));

$url = $rewriter->getUrl('http://www.merchant.com', []);

var_dump($url);
