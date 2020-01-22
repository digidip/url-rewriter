<?php

use digidip\Adapters\FilePathCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Strategies\DigidipRewriterStrategy;
use digidip\UrlWriter;

$adapter = new FilePathCircuitBreakerAdapter('/tmp/digidip-circuit.json');
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlWriter($circuit, new DigidipRewriterStrategy(12345));

$url = $rewriter->getUrl('http://www.merchant.com', []);
