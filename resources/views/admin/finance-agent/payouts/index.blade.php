@extends('layouts.admin')

@section('title', 'Finance Agent — Owner Payouts')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 mb-1">Owner Payouts</h1>
                <p class="text-muted mb-0">Ev Sahibi Ödeme Yönetimi</p>
            </div>
            <a href="{{ route('admin.finance-agent.imports.index') }}" class="btn btn-outline-secondary btn-sm">
                <x-icon name="arrow-left" class="me-1" aria-hidden="true" /> Payout Imports
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        @endif

        {{-- Period Filter --}}
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="GET" class="d-flex align-items-center gap-3">
                    <label for="period" class="form-label mb-0 fw-semibold">Dönem:</label>
                    <input type="month" id="period" name="period"
                        value="{{ request('period', $period->toMonthLabel()) }}" class="form-control form-control-sm"
                        style="width:180px">
                    <button type="submit" class="btn btn-secondary btn-sm">Filtrele</button>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        @php
            $totalNet = $payouts->sum('net_owner_payout');
            $draftCount = $payouts->where('payout_status', 'draft')->count();
            $pendingCount = $payouts->where('payout_status', 'pending_approval')->count();
            $paidCount = $payouts->where('payout_status', 'paid')->count();
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Toplam Net Ödeme</div>
                        <div class="h5 fw-bold text-success">{{ number_format($totalNet, 2) }} TRY</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Taslak</div>
                        <div class="h5 fw-bold">{{ $draftCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Onay Bekleyen</div>
                        <div class="h5 fw-bold text-warning">{{ $pendingCount }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted small">Ödendi</div>
                        <div class="h5 fw-bold text-success">{{ $paidCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payouts Table --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Owner Payout Listesi — {{ $period->format() }}</span>
                <span class="badge bg-secondary">{{ $payouts->count() }} kayıt</span>
            </div>
            <div class="card-body p-0">
                @if ($payouts->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <x-icon name="inbox" class="mb-2" style="width:2rem;height:2rem" aria-hidden="true" />
                        <p class="mb-0">Bu dönem için owner payout bulunamadı.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>İlan</th>
                                    <th>Ev Sahibi</th>
                                    <th>Brüt Gelir</th>
                                    <th>Komisyon</th>
                                    <th>Net Ödeme</th>
                                    <th>Reconciliation</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payouts as $payout)
                                    <tr>
                                        <td>{{ $payout->id }}</td>
                                        <td>#{{ $payout->ilan_id }}</td>
                                        <td>#{{ $payout->owner_kisi_id }}</td>
                                        <td>{{ number_format($payout->gross_rental_income, 2) }}</td>
                                        <td class="text-danger">{{ number_format($payout->total_yalihan_commission, 2) }}
                                        </td>
                                        <td class="text-success fw-semibold">
                                            {{ number_format($payout->net_owner_payout, 2) }} {{ $payout->currency }}</td>
                                        <td>{{ $payout->reconciliation_count }} rez.</td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'draft' => 'secondary',
                                                    'pending_approval' => 'warning',
                                                    'approved' => 'info',
                                                    'paid' => 'success',
                                                    'cancelled' => 'danger',
                                                ];
                                                $badge = $statusMap[$payout->payout_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $badge }}">{{ $payout->payout_status }}</span>
                                        </td>
                                        <td>
                                            @if ($payout->payout_status === 'pending_approval' || $payout->payout_status === 'draft')
                                                <form method="POST"
                                                    action="{{ route('admin.finance-agent.payouts.approve', $payout->id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Ödemeyi onaylamak istediğinize emin misiniz?')">
                                                        Onayla
                                                    </button>
                                                </form>
                                            @elseif($payout->payout_status === 'approved')
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#paidModal{{ $payout->id }}">
                                                    Ödendi İşaretle
                                                </button>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Mark Paid Modal --}}
                                    @if ($payout->payout_status === 'approved')
                                        <div class="modal fade" id="paidModal{{ $payout->id }}" tabindex="-1"
                                            aria-labelledby="paidModalLabel{{ $payout->id }}" aria-modal="true"
                                            role="dialog">
                                            <div class="modal-dialog modal-sm">
                                                <div class="modal-content">
                                                    <form method="POST"
                                                        action="{{ route('admin.finance-agent.payouts.mark-paid', $payout->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h6 class="modal-title" id="paidModalLabel{{ $payout->id }}">
                                                                Ödeme Referansı — #{{ $payout->id }}
                                                            </h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Kapat"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <label for="payment_ref_{{ $payout->id }}"
                                                                class="form-label">Ödeme Referans No</label>
                                                            <input type="text" id="payment_ref_{{ $payout->id }}"
                                                                name="payment_reference" class="form-control" required
                                                                placeholder="Banka işlem no / EFT referansı">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">İptal</button>
                                                            <button type="submit"
                                                                class="btn btn-primary btn-sm">Onayla</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
