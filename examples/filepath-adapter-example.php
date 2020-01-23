<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use digidip\Adapters\FilePathCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Strategies\DigidipRewriterStrategy;
use digidip\UrlRewriter;

$adapter = new FilePathCircuitBreakerAdapter('/tmp/digidip-circuit.json');
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlRewriter($circuit, new DigidipRewriterStrategy(12345));

$url = $rewriter->getUrl('http://www.merchant.com', []);

var_dump($url);
