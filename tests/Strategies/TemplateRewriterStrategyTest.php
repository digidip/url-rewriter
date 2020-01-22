<?php

use PHPUnit\Framework\TestCase;
use digidip\Strategies\TemplateRewriterStrategy;

/**
 * TemplateRewriterStrategyTest
 * @group Strategies
 */
class TemplateRewriterStrategyTest extends TestCase
{
    /** @test */
    public function test_parseOfBasicUrl()
    {
        $strategy = new TemplateRewriterStrategy('http://www.test.com/visit?url={url}');
        $this->assertEquals('http://www.test.com/visit?url=https%3A%2F%2Fwww.buymygoods.com', $strategy->parse('https://www.buymygoods.com', []));
    }

    /** @test */
    public function test_parseOfUrlWithAdditionalArguments()
    {
        $strategy = new TemplateRewriterStrategy('http://www.test.com/visit?url={url}&pid={pid}', [
            'preserveUnpopulatedTokens' => true,
        ]);
        $this->assertEquals('http://www.test.com/visit?url=https%3A%2F%2Fwww.buymygoods.com&pid=123456', $strategy->parse('https://www.buymygoods.com', [
            'pid' => 123456
        ]));

        $this->assertEquals('http://www.test.com/visit?url=https%3A%2F%2Fwww.buymygoods.com&pid={pid}', $strategy->parse('https://www.buymygoods.com', []));
    }

    /** @test */
    public function test_parse_WithOption_preserveUnpopulatedTokens_False()
    {
        $strategy = new TemplateRewriterStrategy('http://www.test.com/visit?url={url}&pid={pid}&cat={name}', [
            'preserveUnpopulatedTokens' => false,
        ]);
        $this->assertEquals('http://www.test.com/visit?url=https%3A%2F%2Fwww.buymygoods.com&pid=123456&cat=', $strategy->parse('https://www.buymygoods.com', [
            'pid' => 123456
        ]));
        $this->assertEquals('http://www.test.com/visit?url=https%3A%2F%2Fwww.buymygoods.com&pid=&cat=', $strategy->parse('https://www.buymygoods.com', []));
    }
}
