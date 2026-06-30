<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class AILandPlotAnalysis extends Model
{
    protected $table = 'ai_land_plot_analyses';

    protected $fillable = [
        'parsel_id',
        'danisman_id',
        'analysis_result',
        'aktiflik_durumu',
        'metadata',
    ];

    protected $casts = [
        'analysis_result' => 'array',
        'metadata' => 'array',
        'aktiflik_durumu' => 'boolean',
    ];
}
