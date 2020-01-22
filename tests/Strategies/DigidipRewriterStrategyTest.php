<?php

use digidip\Strategies\DigidipRewriterStrategy;
use PHPUnit\Framework\TestCase;

/**
 * TemplateRewriterStrategyTest
 * @group Strategies
 */
class DigidipRewriterStrategyTest extends TestCase
{
       /** @test */
       public function test_parseOfBasicUrl()
       {
           $strategy = new DigidipRewriterStrategy(12345);
           $this->assertEquals('https://visit.digidip.net/visit?pid=12345&url=https%3A%2F%2Fwww.buymygoods.com', $strategy->parse('https://www.buymygoods.com', []));
       }
}
