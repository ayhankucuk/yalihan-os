<div wire:poll.30s="loadData">
    <div class="container-fluid py-4">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1 class="h3 mb-0 text-gray-800">Yönetişim (Governance) Paneli</h1>
                <p class="text-muted small mb-0">Gerçek zamanlı davranışsal telemetri ve doğrulama</p>
            </div>
            <div class="col-auto">
                <span class="text-muted small mr-2">Son Güncelleme: {{ $lastUpdated }}</span>
                <button wire:click="loadData" wire:loading.attr="disabled" class="btn btn-sm btn-primary shadow-sm">
                    <span wire:loading.remove wire:target="loadData"><i class="fas fa-sync-alt fa-sm text-white-50"></i> Yenile</span>
                    <span wire:loading wire:target="loadData" class="spinner-border spinner-border-sm" role="status"></span>
                </button>
            </div>
        </div>

        <div wire:loading.class="opacity-50" class="row">
            
            <!-- BÖLÜM 1: Overall Health Score -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card shadow h-100 py-2 border-left-{{ ($healthScore['overall'] ?? 0) >= 95 ? 'success' : (($healthScore['overall'] ?? 0) >= 85 ? 'warning' : 'danger') }}">
                    <div class="card-body">
                        <div class="text-center">
                            <h6 class="text-xs font-weight-bold text-uppercase mb-1">Genel Sağlık Skoru</h6>
                            <div class="h1 mb-0 font-weight-bold text-{{ ($healthScore['overall'] ?? 0) >= 95 ? 'success' : (($healthScore['overall'] ?? 0) >= 85 ? 'warning' : 'danger') }}">
                                {{ $healthScore['overall'] ?? '-' }} / 100
                            </div>
                            <div class="mt-2 text-muted small">
                                Durum: <span class="font-weight-bold">{{ strtoupper($healthScore['trend'] ?? 'stable') }}</span>
                            </div>
                        </div>
                        
                        @if(isset($healthScore['error']))
                            <div class="alert alert-danger mt-3 small">
                                <strong>Hata:</strong> {{ $healthScore['error'] }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BÖLÜM 2: Breakdown Tablosu -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Sağlık Detayları (Breakdown)</h6>
                    </div>
                    <div class="card-body">
                        @foreach($healthScore['breakdown'] ?? [] as $category => $score)
                            <h4 class="small font-weight-bold">
                                {{ ucwords(str_replace('_', ' ', $category)) }}
                                <span class="float-right">{{ $score }}%</span>
                            </h4>
                            <div class="progress mb-4">
                                <div class="progress-bar bg-{{ $score >= 95 ? 'success' : ($score >= 85 ? 'warning' : 'danger') }}" 
                                     role="progressbar" style="width: {{ $score }}%" 
                                     aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- BÖLÜM 3: Drift Durumu -->
            <div class="col-12 mb-4">
                <div class="card shadow border-left-{{ $drift['has_drift'] ? 'warning' : 'success' }}">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Yönetişim Kayması (Drift Detection)</div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                            @if($drift['has_drift'])
                                                <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> KAYMA TESPİT EDİLDİ</span>
                                            @else
                                                <span class="text-success"><i class="fas fa-check-circle"></i> STABİL</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col text-muted small">
                                        Current: {{ $drift['current_rate'] ?? 0 }}% | Baseline: {{ $drift['baseline_rate'] ?? 0 }}% | Threshold: {{ $drift['threshold'] ?? 10 }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BÖLÜM 4: Anomaliler -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Anomali Takibi</h6>
                    </div>
                    <div class="card-body">
                        @if(empty($anomalies))
                            <div class="text-center py-4">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <p class="text-success mb-0">Herhangi bir anomali tespit edilmedi.</p>
                            </div>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach($anomalies as $anomaly)
                                    <li class="list-group-item border-left-{{ $anomaly['severity'] == 'high' ? 'danger' : 'warning' }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ strtoupper($anomaly['type']) }}</strong>
                                                <div class="small text-muted">{{ $anomaly['message'] }}</div>
                                            </div>
                                            <span class="badge badge-{{ $anomaly['severity'] == 'high' ? 'danger' : 'warning' }} px-3 py-2">
                                                {{ strtoupper($anomaly['severity']) }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- BÖLÜM 5: Aktif Alarmlar -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Aktif Alarmlar</h6>
                        <span class="badge badge-danger">{{ count($activeAlerts) }} Aktif</span>
                    </div>
                    <div class="card-body p-0">
                        @if(empty($activeAlerts))
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted mb-0">Aktif alarm bulunmuyor.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light small font-weight-bold">
                                        <tr>
                                            <th>Tip</th>
                                            <th>Zaman</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activeAlerts as $alert)
                                            <tr class="{{ $alert->severity == 'critical' ? 'table-danger' : '' }}">
                                                <td>
                                                    <span class="badge badge-pill badge-{{ $alert->severity == 'critical' ? 'danger' : 'warning' }} mr-2">&nbsp;</span>
                                                    {{ str_replace('governance_', '', $alert->type) }}
                                                </td>
                                                <td class="small text-muted">{{ \Carbon\Carbon::parse($alert->created_at)->diffForHumans() }}</td>
                                                <td>
                                                    <button wire:click="acknowledgeAlert({{ $alert->id }})" class="btn btn-xs btn-outline-success">
                                                        <i class="fas fa-check"></i> Onayla
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
