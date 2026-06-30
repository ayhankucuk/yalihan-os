<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Rapor' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            opacity: 0.9;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #ddd;
        }
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        tr:hover {
            background: #e9ecef;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #10b981;
            color: white;
        }
        .badge-danger {
            background: #ef4444;
            color: white;
        }
        .badge-warning {
            background: #f59e0b;
            color: white;
        }
        .badge-info {
            background: #3b82f6;
            color: white;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $title ?? 'Rapor' }}</h1>
        <p>Oluşturulma Tarihi: {{ now()->format('d.m.Y H:i') }}</p>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Toplam Kayıt:</span>
            <span class="info-value">{{ $data->count() }} adet</span>
        </div>
        <div class="info-row">
            <span class="info-label">Rapor Tipi:</span>
            <span class="info-value">{{ ucfirst($type) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Sayfa:</span>
            <span class="info-value">{{ $data->count() }} kayıt</span>
        </div>
    </div>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @if($type === 'ilan')
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->baslik ?? '-' }}</td>
                        <td class="text-right">{{ number_format($row->fiyat ?? 0, 2, ',', '.') }}</td>
                        <td>{{ $row->para_birimi ?? 'TRY' }}</td>
                        <td>
                            <span class="badge badge-{{ $row->yayin_durumu === 'Aktif' ? 'success' : 'danger' }}">
                                {{ $row->yayin_durumu ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $row->ilanSahibi ? ($row->ilanSahibi->ad . ' ' . $row->ilanSahibi->soyad) : '-' }}</td>
                        <td>{{ $row->il->il_adi ?? '-' }}</td>
                        <td>{{ $row->ilce->ilce_adi ?? '-' }}</td>
                        <td>{{ $row->anaKategori->name ?? '-' }}</td>
                        <td>{{ $row->altKategori->name ?? '-' }}</td>
                        <td>{{ $row->created_at ? $row->created_at->format('d.m.Y') : '-' }}</td>
                    @elseif($type === 'kisi')
                        <td>{{ $row->id }}</td>
                        <td>{{ trim(($row->ad ?? '') . ' ' . ($row->soyad ?? '')) }}</td>
                        <td>{{ $row->telefon ?? '-' }}</td>
                        <td>{{ $row->email ?? '-' }}</td>
                        <td>{{ $row->kisi_tipi ?? $row->musteri_tipi ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $row->aktiflik_durumu ? 'success' : 'danger' }}">
                                {{ $row->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td>{{ $row->danisman->name ?? '-' }}</td>
                        <td>{{ $row->il->il_adi ?? '-' }}</td>
                        <td>{{ $row->ilce->ilce_adi ?? '-' }}</td>
                        <td>{{ $row->created_at ? $row->created_at->format('d.m.Y') : '-' }}</td>
                    @elseif($type === 'talep')
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->baslik ?? '-' }}</td>
                        <td>{{ $row->tip ?? '-' }}</td>
                        <td>
                            <span class="badge badge-info">{{ $row->talep_durumu ?? '-' }}</span>
                        </td>
                        <td>{{ $row->kisi ? ($row->kisi->ad . ' ' . $row->kisi->soyad) : '-' }}</td>
                        <td>{{ $row->kisi ? ($row->kisi->telefon ?? '-') : '-' }}</td>
                        <td>{{ $row->il->il_adi ?? '-' }}</td>
                        <td>{{ $row->ilce->ilce_adi ?? '-' }}</td>
                        <td>{{ $row->created_at ? $row->created_at->format('d.m.Y') : '-' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center">Kayıt bulunamadı</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Bu rapor {{ config('app.name', 'Yalıhan Emlak') }} tarafından otomatik olarak oluşturulmuştur.</p>
        <p>{{ now()->format('d.m.Y H:i:s') }} - Sayfa 1</p>
    </div>
</body>
</html>

