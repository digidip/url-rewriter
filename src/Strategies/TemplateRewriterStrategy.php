<?php

namespace digidip\Strategies;

use digidip\Contracts\RewriterStrategy;

class TemplateRewriterStrategy implements RewriterStrategy
{
    /**
     * @var string
     */
    private $urlTemplate;

    /**
     * @var array
     */
    private $options = [
        'preserveUnpopulatedTokens' => true,
    ];

    /**
     * @param string $urlTemplate   Example: "https://www.moo.com/visit?url={url}"
     */
    public function __construct(string $urlTemplate, array $options = [])
    {
        $this->urlTemplate = $urlTemplate;
        $this->options = array_merge($this->options, $options);
    }

    public function parse(string $url, array $additionalArguments): string
    {
        $response = str_replace("{url}", $this->encodeValue($url), $this->urlTemplate);

        foreach ($additionalArguments as $token => $value) {
            $response = str_replace("{{$token}}", $this->encodeValue($value), $response);
        }

        if (!$this->options['preserveUnpopulatedTokens']) {
            $response = preg_replace('/{[a-zA-Z0-9]+}/', '', $response);
        }

        return $response;
    }

    private function encodeValue(string $value): string
    {
        return trim($value) ? urlencode($value) : '';
    }
}
