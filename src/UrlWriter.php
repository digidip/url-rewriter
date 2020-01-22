<?php
namespace digidip;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use digidip\Contracts\RewriterStrategy;
use digidip\Loggers\NullLogger;

class UrlWriter implements LoggerAwareInterface
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var digidip\CircuitBreaker
     */
    private $circuitBreaker;

    /**
     * @var digidip\Contracts\RewriterStrategy
     */
    private $strategy;

    public function __construct(
        CircuitBreaker $circuitBreaker,
        RewriterStrategy $strategy,
        LoggerInterface $logger = null
    ) {
        $this->setLogger($logger ?: new NullLogger());
        $this->strategy = $strategy;
        $this->circuitBreaker = $circuitBreaker;
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function getUrl(string $urlToRewrite, array $additionalArguments = []): string {
        $this->circuitBreaker->evaluateHealth();

        if ($this->circuitBreaker->isOpen()) {
            $this->logger->warning('Circuit breaker is open, returning url without rewriting.');
            return $urlToRewrite;
        }

        return $this->strategy->parse($urlToRewrite, $additionalArguments);
    }
}