<?php

declare(strict_types=1);

namespace App\Console\Commands\AI;

use App\Models\AiLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AICostReportCommand extends Command
{
    protected $signature = 'ai:cost-report 
                            {--days=7 : The number of days to report on}
                            {--user= : Filter report by User ID}';

    protected $description = 'Generate a report on AI cost, tokens, and model usage';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $userId = $this->option('user');

        $this->info("📊 Generating AI Cost and Token Usage Report (Last {$days} Days)...");
        $this->line(str_repeat('=', 50));

        $startDate = Carbon::now()->subDays($days);

        // 1. Aggregates by Provider / Model
        $query = AiLog::select('provider', 'model', 
            DB::raw('count(*) as total_requests'),
            DB::raw('sum(input_tokens) as total_input_tokens'),
            DB::raw('sum(output_tokens) as total_output_tokens'),
            DB::raw('sum(maliyet_usd) as total_cost_usd')
        )
        ->where('olusturma_tarihi', '>=', $startDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $metrics = $query->groupBy('provider', 'model')
            ->orderBy('total_cost_usd', 'desc')
            ->get();

        if ($metrics->isEmpty()) {
            $this->warn('ℹ️ No AI usage logs found for the selected period.');
            return 0;
        }

        $this->info('📈 Usage by Provider & Model:');
        $headers = ['Provider', 'Model', 'Requests', 'Input Tokens', 'Output Tokens', 'Total Cost (USD)'];
        $rows = [];
        $grandTotalCost = 0.0;
        $grandTotalRequests = 0;

        foreach ($metrics as $metric) {
            $cost = (float) $metric->total_cost_usd;
            $grandTotalCost += $cost;
            $grandTotalRequests += (int) $metric->total_requests;

            $rows[] = [
                $metric->provider,
                $metric->model ?? '-',
                $metric->total_requests,
                number_format((float) $metric->total_input_tokens),
                number_format((float) $metric->total_output_tokens),
                '$' . number_format($cost, 6),
            ];
        }

        $this->table($headers, $rows);
        $this->info("💵 Grand Total Cost: $" . number_format($grandTotalCost, 6));
        $this->info("📞 Grand Total Requests: " . number_format($grandTotalRequests));

        // 2. Daily Cost Trend
        $this->newLine();
        $this->info('📅 Daily Cost Trend:');
        
        $trendQuery = AiLog::select(
            DB::raw('DATE(olusturma_tarihi) as date'),
            DB::raw('count(*) as requests'),
            DB::raw('sum(maliyet_usd) as cost')
        )
        ->where('olusturma_tarihi', '>=', $startDate);

        if ($userId) {
            $trendQuery->where('user_id', $userId);
        }

        $trends = $trendQuery->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $trendHeaders = ['Date', 'Requests', 'Cost (USD)'];
        $trendRows = [];
        foreach ($trends as $trend) {
            $trendRows[] = [
                $trend->date,
                $trend->requests,
                '$' . number_format((float) $trend->cost, 6),
            ];
        }
        $this->table($trendHeaders, $trendRows);

        return 0;
    }
}
