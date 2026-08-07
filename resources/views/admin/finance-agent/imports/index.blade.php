@extends('layouts.admin')

@section('title', 'Finance Agent — Payout Imports')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h4 mb-1">Finance Agent</h1>
                <p class="text-muted mb-0">Airbnb Payout Import &amp; Reconciliation</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.finance-agent.payouts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <x-icon name="wallet" class="me-1" aria-hidden="true" /> Owner Payouts
                </a>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <x-icon name="plus" class="me-1" aria-hidden="true" /> Yeni Import
                </button>
            </div>
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

        {{-- Imports Table --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Import Listesi — {{ $period->format() }}</span>
                <span class="badge bg-secondary">{{ $imports->count() }} kayıt</span>
            </div>
            <div class="card-body p-0">
                @if ($imports->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <x-icon name="inbox" class="mb-2" style="width:2rem;height:2rem" aria-hidden="true" />
                        <p class="mb-0">Bu dönem için import bulunamadı.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Airbnb Payout ID</th>
                                    <th>Dönem</th>
                                    <th>Net Tutar</th>
                                    <th>Para Birimi</th>
                                    <th>Durum</th>
                                    <th>Reconciliation</th>
                                    <th>Import Tarihi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($imports as $import)
                                    <tr>
                                        <td>{{ $import->id }}</td>
                                        <td><code>{{ $import->airbnb_payout_id }}</code></td>
                                        <td>{{ $import->period_start->format('d.m.Y') }} –
                                            {{ $import->period_end->format('d.m.Y') }}</td>
                                        <td class="fw-semibold">{{ number_format($import->net_amount, 2) }}</td>
                                        <td>{{ $import->currency }}</td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'reconciled' => 'success',
                                                    'failed' => 'danger',
                                                ];
                                                $badge = $statusMap[$import->import_status] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $badge }}">{{ $import->import_status }}</span>
                                        </td>
                                        <td>{{ $import->reconciliations->count() }} eşleşme</td>
                                        <td>{{ $import->imported_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                        <td>
                                            <a href="{{ route('admin.finance-agent.imports.show', $import->id) }}"
                                                class="btn btn-outline-primary btn-sm">Detay</a>
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

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.finance-agent.imports.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Yeni Airbnb Payout Import</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="airbnb_payout_id" class="form-label">Airbnb Payout ID <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="airbnb_payout_id" name="airbnb_payout_id"
                                class="form-control @error('airbnb_payout_id') is-invalid @enderror" required
                                placeholder="Airbnb payout transaction ID">
                            @error('airbnb_payout_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="period_start" class="form-label">Dönem Başlangıcı <span
                                        class="text-danger">*</span></label>
                                <input type="date" id="period_start" name="period_start"
                                    class="form-control @error('period_start') is-invalid @enderror" required>
                                @error('period_start')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label for="period_end" class="form-label">Dönem Bitişi <span
                                        class="text-danger">*</span></label>
                                <input type="date" id="period_end" name="period_end"
                                    class="form-control @error('period_end') is-invalid @enderror" required>
                                @error('period_end')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label for="gross_amount" class="form-label">Brüt Tutar <span
                                        class="text-danger">*</span></label>
                                <input type="number" id="gross_amount" name="gross_amount" step="0.01"
                                    min="0" class="form-control @error('gross_amount') is-invalid @enderror"
                                    required>
                                @error('gross_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-4 mb-3">
                                <label for="airbnb_fees" class="form-label">Airbnb Ücretleri</label>
                                <input type="number" id="airbnb_fees" name="airbnb_fees" step="0.01" min="0"
                                    class="form-control" value="0">
                            </div>
                            <div class="col-4 mb-3">
                                <label for="net_amount" class="form-label">Net Tutar <span
                                        class="text-danger">*</span></label>
                                <input type="number" id="net_amount" name="net_amount" step="0.01" min="0"
                                    class="form-control @error('net_amount') is-invalid @enderror" required>
                                @error('net_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="currency" class="form-label">Para Birimi</label>
                            <select id="currency" name="currency" class="form-select">
                                <option value="TRY" selected>TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Import Et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
