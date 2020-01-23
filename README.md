
# URL Rewriter for digidp GmbH visit links

<!-- toc -->
  - [Key Concepts](#key-concepts)
    - [Url Writer](#url-writer)
    - [Circuit Breaker](#circuit-breaker)
      - [Circuit Breaker Options (`?array $options = []`)](#circuit-breaker-options-array-options)
    - [Url Writer Strategies](#url-writer-strategies)
    - [Circuit Breaker Adapters](#circuit-breaker-adapters)
  - [Setup / Installation](#setup--installation)
  - [Examples](#examples)
<!-- tocstop -->

This library was created to allow digidip customers to rewrite their affiliate URLs into digidip affiliate URLs for tracking purchases etc. The library takes advantage of a circuit breaker methodology to help reduce the number of users being redirected via digidip's servers when a server or network issue exists. When a server outage is detected, the circuit will become open and all rewritten URLs will be returned as the original URL provided to the library.

If the circuit is open, it means that the transaction can not be tracked by digidip, although a majority of users will experience a positive redirection to the merchant's page instead of experiencing a error page.

## Key Concepts

- An explanation of the [circuit breaker pattern](https://martinfowler.com/bliki/CircuitBreaker.html).
- When the failure count is zero, then the circuit is considered `Closed`.
- When the failure count is greater than zero, but less than the failure threshold, the circuit is considered `Half-Open`.
- When the failure count is greater than zero and equal to the failure threshold, the circuit is considered `Open`.
- When the circuit is `Closed` or `Half-Open`, the UrlRewriter theoretically will convert `https://mymerchant.com` to `http://visit.digidip.net/visit?url=http%3A%2F%2Fmymerchant.com`.
- When the circuit is `Open`, the UrlRewriter will simply return `https://mymerchant.com` with no rewriter strategy being used.

### Url Writer

This class is used to bind the Circuit Breaker and URL Writer strageies together, ideally you should only need to interact with this class in your existing code base. Reference **Url Writer Strategies** below for available strategies.

```php
use digidip\UrlRewriter;

$urlRewriter = new UrlRewriter(
    // ... CircuitBreaker $circuitBreaker,
    // ... RewriterStrategy $strategy,
    // ... LoggerInterface $logger = null       (PSR Logger Instance)
);

$url = $urlRewriter->getUrl('https://www.a-merchant.com/'); // Return a URL whiuch is defined by the <Method>RewriterStrategy.
```

### Circuit Breaker

The circuit breaker class is the responsible on whether the URL Rewriter should perform a URL rewrite or not. It minimal requirements are an adapter which provides the Circuit Breaker a mechanism to communicate with an external storage resource, such as a file, Redis, Memcache or any bespoke method which can be implemented with the `CircuitBreakerAdapter` interface. Reference **Circuit Breaker Adapters** below for available adapters.

```php
use digidip\CircuitBreaker;
use digidip\Adapters\FilePathCircuitBreakerAdapter;

$adapter = new FilePathCircuitBreakerAdapter('/path/to/file.json');
$circuit = new CircuitBreaker(
    $adapter,
    // ... ?array $options = [],
    // ... ?string $url = null,                 For testing purposes
    // ... ?Client $client = null,              For testing purposes
    // ... ?LoggerInterface $logger = null      (PSR Logger Instance)
);
```

#### Circuit Breaker Options (`?array $options = []`)

- `CircuitBreaker::OPTION_TIMEOUT` - Number of milliseconds before the HTTP request will timeout and be considered a service failure. *Default: 1000*
- `CircuitBreaker::OPTION_FAILURE_THRESHOLD` - The amount of failures which must occur before opening the circuit. *Default: 5*
- `CircuitBreaker::OPTION_OPENED_SAMPLE_RATE` - The number of seconds between polling the service's availablity, once a single failure has been detected, this sample rate will be used. *Default: 1*
- `CircuitBreaker::OPTION_CLOSED_SAMPLE_RATE` - The number of seconds between polling the service's availablity while no failures are detected. *Default: 5*

### Url Writer Strategies

Currently available stategies include:
- Standard Digidip Rewriter Strategy - `DigidipRewriterStrategy(pid)`
- Subdomain based Digidip Rewriter Strategy - `DigidipSubdomainRewriterStrategy(subdomain)`
- Template Rewriter - `TemplateRewriterStrategy(url template)`

### Circuit Breaker Adapters

Currently available adapters include:
- File Path - `FilePathCircuitBreakerAdapter(filepath)`
- File reader and writer interfaces - `FileCircuitBreakerAdapter(Reader, Writer)`, reference `src/Modules/Filesystem` for available Readers and Writers.
- Redis - `RedisCircuitBreakerAdapter(\Redis instance)`
- Memcached - `MemcachedCircuitBreakerAdapter(\Memcached instance)`

## Installation

Execute `composer require digidip/url-rewriter` in your project.

## Examples

Please see the examples directory.