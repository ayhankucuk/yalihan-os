@extends('admin.layouts.app')

@section('title', 'Cache İstatistikleri')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">📊 Redis Cache İstatistikleri</h1>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Hafıza Kullanımı</h5>
                </div>
                <div class="p-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Kullanılan Hafıza:</dt>
                        <dd class="col-sm-6">{{ number_format($redis['used_memory'] / 1024 / 1024, 2) }} MB</dd>

                        <dt class="col-sm-6">Toplam Sistem Hafızası:</dt>
                        <dd class="col-sm-6">{{ number_format($redis['total_system_memory'] / 1024 / 1024, 2) }} MB</dd>

                        <dt class="col-sm-6">Peak Hafıza:</dt>
                        <dd class="col-sm-6">{{ number_format($redis['used_memory_peak'] / 1024 / 1024, 2) }} MB</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Bağlantı Bilgileri</h5>
                </div>
                <div class="p-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Bağlı İstemciler:</dt>
                        <dd class="col-sm-6">{{ $redis['connected_clients'] }}</dd>

                        <dt class="col-sm-6">Toplam Komut:</dt>
                        <dd class="col-sm-6">{{ number_format($redis['total_commands_processed']) }}</dd>

                        <dt class="col-sm-6">Uptime:</dt>
                        <dd class="col-sm-6">{{ round($redis['uptime_in_seconds'] / 86400, 1) }} gün</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Key İstatistikleri</h5>
                </div>
                <div class="p-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Toplam Key:</dt>
                        <dd class="col-sm-6">{{ $stats['total_keys'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-6">Evicted Keys:</dt>
                        <dd class="col-sm-6">{{ $redis['evicted_keys'] }}</dd>

                        <dt class="col-sm-6">Expired Keys:</dt>
                        <dd class="col-sm-6">{{ $redis['expired_keys'] }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Yalıhan Cache Stats</h5>
                </div>
                <div class="p-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Hit Rate:</dt>
                        <dd class="col-sm-6">
                            @if(isset($stats['hit_rate']))
                                <strong class="text-{{ $stats['hit_rate'] > 80 ? 'success' : ($stats['hit_rate'] >= 50 ? 'warning' : 'danger') }}">
                                    {{ number_format($stats['hit_rate'], 1) }}%
                                </strong>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </dd>

                        <dt class="col-sm-6">Total Hits:</dt>
                        <dd class="col-sm-6">{{ $stats['hits'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-6">Total Misses:</dt>
                        <dd class="col-sm-6">{{ $stats['misses'] ?? 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('monitoring.api.cache.stats') }}" class="btn btn-outline-primary" target="_blank">
            📄 JSON API
        </a>
        <button class="btn btn-outline-secondary" onclick="location.reload()">
            🔄 Yenile
        </button>
    </div>
</div>
@endsection
