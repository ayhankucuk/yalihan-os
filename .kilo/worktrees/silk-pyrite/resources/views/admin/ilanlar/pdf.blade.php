<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Listesi - {{ date('d.m.Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }

        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 24px;
        }

        .header .date {
            color: #6b7280;
            margin-top: 5px;
        }

        .summary {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .summary h2 {
            margin: 0 0 10px 0;
            color: #374151;
            font-size: 16px;
        }

        .stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .stat-label {
            color: #6b7280;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #3b82f6;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .state {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }

        .state-active {
            background: #dcfce7;
            color: #166534;
        }

        .state-pasif {
            background: #fee2e2;
            color: #991b1b;
        }

        .state-taslak {
            background: #fef3c7;
            color: #92400e;
        }

        .type {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }

        .type-satilik {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-kiralik {
            background: #dcfce7;
            color: #166534;
        }

        .type-gunluk {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .price {
            font-weight: bold;
            color: #059669;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .page-break {
            page-break-before: always;
        }

        @media print {
            body {
                margin: 0;
                padding: 15px;
            }

            .header {
                margin-bottom: 20px;
            }

            table {
                font-size: 9px;
            }

            th,
            td {
                padding: 6px 4px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📋 İlan Listesi Raporu</h1>
        <div class="date">{{ date('d.m.Y H:i') }} - {{ config('app.name') }}</div>
    </div>

    <div class="summary">
        <h2>📊 Özet İstatistikler</h2>
        <div class="stats">
            <div class="stat-item">
                <div class="stat-value">{{ $ilanlar->count() }}</div>
                <div class="stat-label">Toplam İlan</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $ilanlar->filter(fn($i) => $i->yayin_durumu == 'Aktif')->count() }}</div>
                <div class="stat-label">Aktif İlan</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $ilanlar->where('ilan_turu', 'Satılık')->count() }}</div>
                <div class="stat-label">Satılık İlan</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $ilanlar->where('ilan_turu', 'Kiralık')->count() }}</div>
                <div class="stat-label">Kiralık İlan</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($ilanlar->avg('fiyat')) }} ₺</div>
                <div class="stat-label">Ortalama Fiyat</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($ilanlar->sum('fiyat')) }} ₺</div>
                <div class="stat-label">Toplam Değer</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="25%">İlan Başlığı</th>
                <th width="8%">Tür</th>
                <th width="12%">Fiyat</th>
                <th width="15%">Lokasyon</th>
                <th width="8%">M²</th>
                <th width="8%">Oda</th>
                <th width="8%">Durum</th>
                <th width="11%">Tarih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ilanlar as $index => $ilan)
                @if ($index > 0 && $index % 25 == 0)
        </tbody>
    </table>
    <div class="page-break"></div>
    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="25%">İlan Başlığı</th>
                <th width="8%">Tür</th>
                <th width="12%">Fiyat</th>
                <th width="15%">Lokasyon</th>
                <th width="8%">M²</th>
                <th width="8%">Oda</th>
                <th width="8%">Durum</th>
                <th width="11%">Tarih</th>
            </tr>
        </thead>
        <tbody>
            @endif

            <tr>
                <td>#{{ $ilan->id }}</td>
                <td>{{ Str::limit($ilan->ilan_basligi ?? $ilan->baslik, 40) }}</td>
                <td>
                    <span
                        class="type type-{{ strtolower(str_replace(['ı', 'ş', 'ğ', 'ü', 'ö', 'ç', ' '], ['i', 's', 'g', 'u', 'o', 'c', ''], $ilan->ilan_turu ?? '')) }}">
                        {{ $ilan->ilan_turu ?? 'N/A' }}
                    </span>
                </td>
                <td class="price">{{ number_format($ilan->fiyat) }} ₺</td>
                <td>
                    {{ $ilan->il->il_adi ?? 'N/A' }}
                    @if ($ilan->ilce)
                        , {{ $ilan->ilce->ilce_adi }}
                    @endif
                </td>
                <td>{{ $ilan->metrekare ? number_format($ilan->metrekare) . ' m²' : 'N/A' }}</td>
                <td>{{ $ilan->oda_sayisi ?? 'N/A' }}</td>
                <td>
                    @php
                        $ilanDurum = $ilan->yayin_durumu ?? 'pasif';
                    @endphp
                    <span class="state state-{{ strtolower($ilanDurum) }}">
                        {{ $ilan->durum_label ?? $ilanDurum }}
                    </span>
                </td>
                <td>{{ $ilan->created_at ? $ilan->created_at->format('d.m.Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>
            Bu rapor {{ date('d.m.Y H:i') }} tarihinde {{ config('app.name') }} sistemi tarafından otomatik olarak
            oluşturulmuştur.<br>
            Toplam {{ $ilanlar->count() }} ilan listelenmektedir.
        </p>
    </div>
</body>

</html>
