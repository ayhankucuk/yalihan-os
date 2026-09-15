<?php
/**
 * AI Governance Dashboard - Prompt Compliance Telemetry
 *
 * SAB5: Operator Intelligence - AI system monitoring
 * Shows AI prompt governance scores, compliance rates, and violations
 */
?>
@extends('layouts.admin')

@php
use Illuminate\Support\Str;
@endphp

@section('title', 'AI Governance - Yalıhan Emlak')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            🤖 AI Governance
        </h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            AI Prompt Compliance Telemetri — Son 30 günlük performans
        </p>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        {{-- Total Requests --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-700">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam İstek</div>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($summary['total_requests']) }}
            </div>
        </div>

        {{-- Average Score --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-700">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Ortalama Skor</div>
            <div class="mt-2 text-3xl font-bold 
                @if($summary['avg_score'] >= 80)
                    text-green-600 dark:text-green-400
                @elseif($summary['avg_score'] >= 60)
                    text-yellow-600 dark:text-yellow-400
                @else
                    text-red-600 dark:text-red-400
                @endif
            ">
                {{ $summary['avg_score'] }}%
            </div>
        </div>

        {{-- Compliance Rate --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-700">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Uyum Oranı</div>
            <div class="mt-2 text-3xl font-bold 
                @if($summary['compliance_rate'] >= 90)
                    text-green-600 dark:text-green-400
                @elseif($summary['compliance_rate'] >= 70)
                    text-yellow-600 dark:text-yellow-400
                @else
                    text-red-600 dark:text-red-400
                @endif
            ">
                {{ $summary['compliance_rate'] }}%
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                (skor >= 80%)
            </div>
        </div>

        {{-- Risk Level --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-700">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Risk Seviyesi</div>
            <div class="mt-2">
                @if($summary['compliance_rate'] >= 90)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        🟢 Düşük
                    </span>
                @elseif($summary['compliance_rate'] >= 70)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                        🟡 Orta
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        🔴 Yüksek
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Score Distribution --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-gray-200 dark:border-slate-700 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            📊 Skor Dağılımı
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ $score_distribution['critical'] }}
                </div>
                <div class="text-sm text-red-700 dark:text-red-300">Kritik (&lt;50%)</div>
            </div>
            <div class="text-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                    {{ $score_distribution['low'] }}
                </div>
                <div class="text-sm text-orange-700 dark:text-orange-300">Düşük (50-70%)</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ $score_distribution['medium'] }}
                </div>
                <div class="text-sm text-yellow-700 dark:text-yellow-300">Orta (70-90%)</div>
            </div>
            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $score_distribution['high'] }}
                </div>
                <div class="text-sm text-green-700 dark:text-green-300">Yüksek (&gt;=90%)</div>
            </div>
        </div>
    </div>

    {{-- Recent Violations --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow border border-gray-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                ⚠️ Son İhlaller (Skor &lt; 90)
            </h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-slate-700">
            @forelse($recentViolations as $violation)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-slate-700/50">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($violation->governance_score < 50)
                                    bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($violation->governance_score < 70)
                                    bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                @else
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @endif
                            ">
                                {{ $violation->governance_score }}%
                            </span>
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $violation->template->template_json['baslik'] ?? 'Bilinmeyen Şablon' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $violation->created_at->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        </div>
                        @if($violation->violations)
                            <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ Str::limit($violation->violations, 100) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            #{{ $violation->id }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="text-4xl mb-2">✅</div>
                    <div class="text-gray-500 dark:text-gray-400">
                        Son 30 günde ihlal tespit edilmedi
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
