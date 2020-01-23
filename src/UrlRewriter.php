<?php
namespace digidip;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use digidip\Contracts\RewriterStrategy;
use digidip\Loggers\NullLogger;

class UrlRewriter implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
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
        $this->strategy = $strategy;
        $this->circuitBreaker = $circuitBreaker;
        $this->setLogger($logger ?: new NullLogger());
        $this->logger->debug("UrlRewriter::__construct() Called");
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->circuitBreaker->setLogger($this->logger);
    }

    public function getUrl(string $urlToRewrite, array $additionalArguments = []): string {
        $json = json_encode($additionalArguments);
        $this->logger->debug("UrlRewriter::getUrl({$urlToRewrite}, {$json}) called");
        $this->circuitBreaker->evaluateHealth();

        if ($this->circuitBreaker->isOpen()) {
            $this->logger->debug("UrlRewriter::getUrl(...) circuit is open, returning URL '{$urlToRewrite}'.");
            return $urlToRewrite;
        }

        $url = $this->strategy->parse($urlToRewrite, $additionalArguments);
        $this->logger->debug("UrlRewriter::getUrl(...) circuit is closed, returning URL '{$url}'");
        return $url;
    }
}