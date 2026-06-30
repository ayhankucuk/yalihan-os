<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\NLPProcessor;

class NLPProcessorTest extends TestCase
{
    private NLPProcessor $nlp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nlp = new NLPProcessor();
    }

    /** @test */
    public function it_can_extract_expanded_locations_and_handle_typos()
    {
        $result = $this->nlp->parseMessage("Yalikavak ve gumusluk civarında villa arıyorum");
        
        $this->assertContains('Yalıkavak', $result['entities']['locations']);
        $this->assertContains('Gümüşlük', $result['entities']['locations']);
    }

    /** @test */
    public function it_can_classify_investment_intent()
    {
        $result = $this->nlp->parseMessage("Kelepir ve yüksek getirili bir arsa fırsatı var mı?");
        
        $this->assertEquals('investment', $result['intent']);
        $this->assertContains('land', $result['entities']);
    }

    /** @test */
    public function it_can_extract_timeframes()
    {
        $result = $this->nlp->parseMessage("Acil kiralık daire arıyorum, hemen taşınmalıyım");
        
        $this->assertEquals('immediate', $result['entities']['timeframe']);
        $this->assertEquals('rent', $result['intent']);
    }

    /** @test */
    public function it_normalizes_turkish_typos()
    {
        $result = $this->nlp->parseMessage("Bodrumda 100m2 satilik ev");
        
        $this->assertContains('Bodrum', $result['entities']['locations']);
        $this->assertEquals(100, $result['entities']['area']['value']);
        $this->assertEquals('sale', $result['entities']['transaction_type']);
    }
}
