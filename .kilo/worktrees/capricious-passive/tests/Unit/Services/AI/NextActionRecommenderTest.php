<?php

namespace Tests\Unit\Services\AI;

use App\Models\Lead;
use App\Models\AILeadScore;
use App\Services\AI\NextActionRecommender;
use Tests\TestCase;

class NextActionRecommenderTest extends TestCase
{
    private NextActionRecommender $recommender;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recommender = new NextActionRecommender();
    }

    /** @test */
    public function it_recommends_info_completion_for_high_prob_missing_data()
    {
        $lead = new Lead(['budget_max' => null]); // Missing budget
        $score = new AILeadScore(['win_probability' => 60]);

        $recommendation = $this->recommender->recommend($lead, $score);

        $this->assertEquals('Bilgi Tamamla', $recommendation['action']);
        $this->assertStringContainsString('Bütçe', $recommendation['description']);
    }

    /** @test */
    public function it_recommends_call_for_hot_leads()
    {
        $lead = new Lead(['budget_max' => 100, 'interested_location_id' => 1]);
        $score = new AILeadScore(['win_probability' => 80]);

        $recommendation = $this->recommender->recommend($lead, $score);

        $this->assertEquals('Hemen Ara', $recommendation['action']);
        $this->assertEquals('critical', $recommendation['urgency']);
    }
}
