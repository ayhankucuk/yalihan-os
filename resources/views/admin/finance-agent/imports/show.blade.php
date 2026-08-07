@extends('layouts.admin')

@section('title', 'Import Detayı #' . $import->id)

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <a href="{{ route('admin.finance-agent.imports.index') }}" class="text-muted small">
                    ← Import Listesi
                </a>
                <h1 class="h4 mb-0 mt-1">Payout Import #{{ $import->id }}</h1>
            </div>
            @if ($import->isPending() || $import->isFailed())
                <form method="POST" action="{{ route('admin.finance-agent.imports.reconcile', $import->id) }}"
                    class="d-flex align-items-center gap-2">
                    @csrf
                    <label for="commission_rate" class="form-label mb-0 fw-semibold">Komisyon %:</label>
                    <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0"
                        max="100" value="10" class="form-control form-control-sm" style="width:100px">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <x-icon name="refresh-cw" class="me-1" aria-hidden="true" /> Reconcile Et
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        @endif

        {{-- Import Summary --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Net Tutar</div>
                        <div class="h5 fw-bold">{{ number_format($import->net_amount, 2) }} {{ $import->currency }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Dönem</div>
                        <div class="h6 fw-semibold">{{ $import->period_start->format('d.m.Y') }}</div>
                        <div class="text-muted small">– {{ $import->period_end->format('d.m.Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Durum</div>
                        @php
                            $statusMap = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'reconciled' => 'success',
                                'failed' => 'danger',
                            ];
                            $badge = $statusMap[$import->import_status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $badge }} fs-6">{{ $import->import_status }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Reconciliation Sayısı</div>
                        <div class="h5 fw-bold">{{ $import->reconciliations->count() }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reconciliations Table --}}
        <div class="card">
            <div class="card-header fw-semibold">Reconciliation Kayıtları</div>
            <div class="card-body p-0">
                @if ($import->reconciliations->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">Henüz reconciliation kaydı yok. Reconcile Et butonunu kullanın.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Rezervasyon</th>
                                    <th>İlan</th>
                                    <th>Rez. Tutarı</th>
                                    <th>Komisyon %</th>
                                    <th>Komisyon TL</th>
                                    <th>Ev Sahibi Net</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($import->reconciliations as $rec)
                                    <tr>
                                        <td>{{ $rec->id }}</td>
                                        <td>{{ $rec->reservation_id ?? '—' }}</td>
                                        <td>{{ $rec->ilan_id ?? '—' }}</td>
                                        <td>{{ number_format($rec->reservation_amount, 2) }}</td>
                                        <td>%{{ $rec->yalihan_commission_rate }}</td>
                                        <td class="text-danger">{{ number_format($rec->yalihan_commission_amount, 2) }}
                                        </td>
                                        <td class="text-success fw-semibold">{{ number_format($rec->owner_net_amount, 2) }}
                                            {{ $rec->currency }}</td>
                                        <td>
                                            @php
                                                $recMap = [
                                                    'pending' => 'warning',
                                                    'matched' => 'info',
                                                    'approved' => 'success',
                                                    'unmatched' => 'secondary',
                                                    'disputed' => 'danger',
                                                ];
                                                $recBadge = $recMap[$rec->reconciliation_status] ?? 'secondary';
                                            @endphp
                                            <span
                                                class="badge bg-{{ $recBadge }}">{{ $rec->reconciliation_status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-semibold">
                                <tr>
                                    <td colspan="3">Toplam</td>
                                    <td>{{ number_format($import->reconciliations->sum('reservation_amount'), 2) }}</td>
                                    <td></td>
                                    <td class="text-danger">
                                        {{ number_format($import->reconciliations->sum('yalihan_commission_amount'), 2) }}
                                    </td>
                                    <td class="text-success">
                                        {{ number_format($import->reconciliations->sum('owner_net_amount'), 2) }}
                                        {{ $import->currency }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
