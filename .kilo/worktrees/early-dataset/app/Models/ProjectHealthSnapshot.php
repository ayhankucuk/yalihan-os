<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

class ProjectHealthSnapshot extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $fillable = [
        'overall_health_score',
        'context7_compliance_score',
        'code_quality_score',
        'test_coverage_score',
        'performance_score',
        'active_violations', // context7-ignore
        'critical_issues',
        'total_files',
        'total_lines',
        'health_details',
        'recommendations',
        'snapshot_at',
    ];

    protected $casts = [
        'overall_health_score' => 'decimal:2',
        'context7_compliance_score' => 'decimal:2',
        'code_quality_score' => 'decimal:2',
        'test_coverage_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'health_details' => 'array',
        'recommendations' => 'array',
        'snapshot_at' => 'datetime',
    ];

    // Scopes
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('snapshot_at', '>=', now()->subDays($days));
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('snapshot_at', 'desc')->first(); // context7-ignore
    }

    public function scopeHealthy($query, $threshold = 80.0)
    {
        return $query->where('overall_health_score', '>=', $threshold);
    }

    public function scopeUnhealthy($query, $threshold = 60.0)
    {
        return $query->where('overall_health_score', '<', $threshold);
    }

    public function scopeWithCriticalIssues($query)
    {
        return $query->where('critical_issues', '>', 0);
    }

    // Accessors
    public function getHealthStatusAttribute()
    {
        if ($this->overall_health_score >= 90) {
            return 'excellent';
        }
        if ($this->overall_health_score >= 80) {
            return 'good';
        }
        if ($this->overall_health_score >= 70) {
            return 'fair';
        }
        if ($this->overall_health_score >= 60) {
            return 'poor';
        }

        return 'critical';
    }

    public function getHealthColorAttribute()
    {
        return match ($this->health_aktiflik_durumu) {
            'excellent' => 'green',
            'good' => 'blue',
            'fair' => 'yellow',
            'poor' => 'orange',
            'critical' => 'red'
        };
    }

    public function getHealthTrendAttribute()
    {
        $previous = static::where('snapshot_at', '<', $this->snapshot_at)
            ->orderBy('snapshot_at', 'desc') // context7-ignore
            ->first();

        if (! $previous) {
            return 'stable';
        }

        $diff = $this->overall_health_score - $previous->overall_health_score;

        if ($diff > 2) {
            return 'improving';
        }
        if ($diff < -2) {
            return 'declining';
        }

        return 'stable';
    }
}
