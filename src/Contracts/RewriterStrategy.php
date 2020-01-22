<?php

namespace digidip\Contracts;

interface RewriterStrategy {
    function parse(string $url, array $additionalArguments): string;
}