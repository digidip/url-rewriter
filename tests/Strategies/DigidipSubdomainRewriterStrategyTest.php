<?php

use digidip\Strategies\DigidipSubdomainRewriterStrategy;
use PHPUnit\Framework\TestCase;

/**
 * TemplateRewriterStrategyTest
 * @group Strategies
 */
class DigidipSubdomainRewriterStrategyTest extends TestCase
{
    /** @test */
    public function test_parseOfBasicUrl()
    {
        $strategy = new DigidipSubdomainRewriterStrategy('mydomain');
        $this->assertEquals('https://mydomain.digidip.net/visit?url=https%3A%2F%2Fwww.buymygoods.com', $strategy->parse('https://www.buymygoods.com', []));
    }
}
