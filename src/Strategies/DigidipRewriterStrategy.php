<?php

namespace digidip\Strategies;

class DigidipRewriterStrategy extends TemplateRewriterStrategy {
    public function __construct(int $pid, array $options = []) {
        parent::__construct("https://visit.digidip.net/visit?pid={$pid}&url={url}", $options);
    }
}