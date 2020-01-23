<?php

namespace digidip\Strategies;

class DigidipSubdomainRewriterStrategy extends TemplateRewriterStrategy
{
    public function __construct(string $subdomain, array $options = [])
    {
        parent::__construct("https://{$subdomain}.digidip.net/visit?url={url}", $options);
    }
}
