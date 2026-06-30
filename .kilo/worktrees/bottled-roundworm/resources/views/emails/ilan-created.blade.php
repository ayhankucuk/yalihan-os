<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f3f4f6;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .ilan-title {
            font-size: 20px;
            color: #1f2937;
            margin: 0 0 20px 0;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 130px;
        }
        .info-value {
            color: #1f2937;
            flex: 1;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600;
        }
        .button:hover {
            background: #2563eb;
        }
        .footer {
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏠 Yeni İlan Eklendi</h1>
        </div>

        <div class="content">
            <h2 class="ilan-title">{{ $ilan->baslik }}</h2>

            <div class="info-row">
                <span class="info-label">Fiyat:</span>
                <span class="info-value">{{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}</span>
            </div>

            @if($ilan->il)
            <div class="info-row">
                <span class="info-label">Konum:</span>
                <span class="info-value">{{ $ilan->il }}@if($ilan->ilce), {{ $ilan->ilce }}@endif</span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Yayın Durumu:</span>
                <span class="info-value">{{ $ilan->yayin_durumu }}</span>
            </div>

            @if($ilan->danisman)
            <div class="info-row">
                <span class="info-label">Danışman:</span>
                <span class="info-value">{{ $ilan->danisman->ad_soyad ?? 'N/A' }}</span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Oluşturulma:</span>
                <span class="info-value">{{ $ilan->created_at->format('d.m.Y H:i') }}</span>
            </div>

            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}" class="button">
                İlanı Görüntüle →
            </a>
        </div>

        <div class="footer">
            © {{ date('Y') }} Yalıhan Emlak - Otomatik Bildirim Sistemi
        </div>
    </div>
</body>
</html>
