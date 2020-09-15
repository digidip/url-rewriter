<?php

namespace digidip\Strategies;

class DigidipSubdomainRewriterStrategy extends TemplateRewriterStrategy
{
    public function __construct(string $subdomain, array $options = [])
    {
        parent::__construct("https://{$subdomain}.digidip.it/visit?url={url}", $options);
    }
}
