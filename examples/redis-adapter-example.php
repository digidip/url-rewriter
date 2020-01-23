<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use digidip\Adapters\RedisCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Strategies\DigidipSubdomainRewriterStrategy;
use digidip\UrlRewriter;

$redis = new \Redis();
$redis->connect('localhost');

$adapter = new RedisCircuitBreakerAdapter($redis);
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlRewriter($circuit, new DigidipSubdomainRewriterStrategy('mydomain'));

$url = $rewriter->getUrl('http://www.merchant.com', []);

var_dump($url);
