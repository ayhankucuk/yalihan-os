@extends('admin.layouts.app')

@section('title', 'Sistem Sağlığı')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">🏥 Sistem Sağlığı</h1>
        </div>
    </div>

    <div class="row g-4">
        <!-- Database -->
        <div class="col-md-4">
            <div class="card @if($health['database']['yayin_durumu'] === 'healthy') border-success @else border-danger @endif">
                <div class="card-body text-center">
                    <div class="display-4 mb-3">
                        @if($health['database']['yayin_durumu'] === 'healthy')
                            <span class="text-success">✅</span>
                        @else
                            <span class="text-danger">❌</span>
                        @endif
                    </div>
                    <h5 class="card-title">Database</h5>
                    <p class="card-text">{{ $health['database']['message'] ?? $health['database']['yayin_durumu'] }}</p>
                </div>
            </div>
        </div>

        <!-- Cache -->
        <div class="col-md-4">
            <div class="card @if($health['cache']['yayin_durumu'] === 'healthy') border-success @else border-danger @endif">
                <div class="card-body text-center">
                    <div class="display-4 mb-3">
                        @if($health['cache']['yayin_durumu'] === 'healthy')
                            <span class="text-success">✅</span>
                        @else
                            <span class="text-danger">❌</span>
                        @endif
                    </div>
                    <h5 class="card-title">Cache (Redis)</h5>
                    <p class="card-text">{{ $health['cache']['message'] ?? $health['cache']['yayin_durumu'] }}</p>
                </div>
            </div>
        </div>

        <!-- Disk -->
        <div class="col-md-4">
            <div class="card @if($health['disk']['yayin_durumu'] === 'healthy') border-success @elseif($health['disk']['yayin_durumu'] === 'warning') border-warning @else border-danger @endif">
                <div class="card-body text-center">
                    <div class="display-4 mb-3">
                        @if($health['disk']['yayin_durumu'] === 'healthy')
                            <span class="text-success">✅</span>
                        @elseif($health['disk']['yayin_durumu'] === 'warning')
                            <span class="text-warning">⚠️</span>
                        @else
                            <span class="text-danger">❌</span>
                        @endif
                    </div>
                    <h5 class="card-title">Disk Space</h5>
                    <p class="card-text">{{ $health['disk']['free_percentage'] }}% Free</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transition-all duration-200">
                <div class="p-6">
                    <p class="text-muted mb-0">
                        <small>Son Kontrol: {{ \Carbon\Carbon::parse($health['timestamp'])->format('d.m.Y H:i:s') }}</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
