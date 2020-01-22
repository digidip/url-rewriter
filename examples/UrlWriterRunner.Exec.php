<?php

/**
 * This script simulates a request to rewrite a URL every 500ms, outputting the generated URL and the status
 * code the circuit.
 *
 * To get started, you must use this script in conjunction with the supplied test server configured in a docker
 * container. The software that runs the server is called Node-Red (https://nodered.org/docs/) which allows you
 * to quickly re-configure your webserver for simulating and obeserving the behaviour of the URL rewriter's
 * circuit breaker.
 *
 * 1. To start the test server, type the following in the project directory:
 *   `~/url-writer $> docker-compose up --build`.  (please make sure port 1880 is available)
 *
 * 2. Once the container is up and running, go to http://127.0.0.1:1880/
 *
 * 3. Then execute: `~/url-writer $> php tests/UrlWriterRunner.Exec.php
 *
 * 4. In the node-red tool, you are able to switch the HTTP status and simulate a timeout event using
 *    the provided buttons.
 */
require_once(__DIR__ . '/../vendor/autoload.php');

use digidip\Adapters\FileCircuitBreakerAdapter;
use digidip\Adapters\FilePathCircuitBreakerAdapter;
use digidip\CircuitBreaker;
use digidip\Loggers\NullLogger;
use digidip\Loggers\StdioLogger;
use digidip\Modules\Filesystem\StandardFileReader;
use digidip\Modules\Filesystem\StandardFileWriter;
use digidip\Modules\Filesystem\TestFileReader;
use digidip\Modules\Filesystem\TestFileWriter;
use digidip\Strategies\TemplateRewriterStrategy;
use digidip\UrlWriter;

$logger = new NullLogger();
if ($argc >= 2 && in_array('--debug', $argv)) {
    $logger = new StdioLogger();
}

// $buffer = '';
$count = 0;
$lastSample = null;
while (true) {
    // $reader = new StandardFileReader($buffer);
    // $writer = new StandardFileWriter($buffer);
    $adapter = new FilePathCircuitBreakerAdapter(__DIR__ . '/data.json');
    $circuit = new CircuitBreaker(
        $adapter,
        [
            CircuitBreaker::OPTION_FAILURE_THRESHOLD => 3,
        ],
        'http://127.0.0.1:1880/visit',
        null,
        $logger
    );

    $rewriter = new UrlWriter(
        $circuit,
        new TemplateRewriterStrategy('http://visit.digidip.net/visit?url={url}'),
        $logger
    );

    $url = $rewriter->getUrl('http://www.merchant.com', []);
    $isOpen = ($circuit->isOpen() === true) ? "\e[41m\e[30m  Open  \e[0m" : "\e[42m\e[30m Closed \e[0m";
    $failrecount = $adapter->getFailureCount() > 0 ? "\e[93m{$adapter->getFailureCount()}\e[0m" : "\e[92m{$adapter->getFailureCount()}\e[0m";
    $sampleRate = "\e[1m{$adapter->getSampleRate()}\e[0m";
    $counter = str_pad(++$count, 4, '0', STR_PAD_LEFT);

    $status = '  ';
    if ($lastSample !== $adapter->getLastSampleTimestamp()) {
        // new update
        $status = '📡';
        $lastSample = $adapter->getLastSampleTimestamp();
    }

    fwrite(STDOUT, "[\e[1m{$counter}\e[0m] --> Circuit Breaker is {$isOpen}, Failures: [{$failrecount}], SR: [{$sampleRate}] {$status} -- Rewritten URL: \e[1m\e[4m{$url}\e[0m\n");

    if ($argc >= 2 && in_array('--verbose', $argv)) {
        fwrite(STDOUT, "\n$buffer\n\n");
    }

    usleep(500000);
}
