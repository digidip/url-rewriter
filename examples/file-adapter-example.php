<?php

require_once(__DIR__ . '/../vendor/autoload.php');

use digidip\Adapters\FileCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Modules\Filesystem\StandardFileReader;
use digidip\Modules\Filesystem\StandardFileWriter;
use digidip\Strategies\DigidipSubdomainRewriterStrategy;
use digidip\UrlRewriter;

$adapter = new FileCircuitBreakerAdapter(
    new StandardFileReader('/tmp/digidip-circuit.json'),
    new StandardFileWriter('/tmp/digidip-circuit.json')
);
$circuit = new CircuitBreaker($adapter);
$rewriter = new UrlRewriter($circuit, new DigidipSubdomainRewriterStrategy('mydomain'));

$url = $rewriter->getUrl('http://www.merchant.com', []);

var_dump($url);
