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
        .header-subtitle {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .section-title {
            font-size: 16px;
            color: #1f2937;
            margin: 0 0 16px 0;
            font-weight: 600;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
        }
        .info-card {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 140px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-label svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .info-value {
            color: #1f2937;
            flex: 1;
            display: flex;
            align-items: center;
        }
        .message-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin-top: 16px;
            border-radius: 0 8px 8px 0;
        }
        .message-box .label {
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message-box .label svg {
            width: 16px;
            height: 16px;
        }
        .message-box .text {
            color: #1f2937;
            line-height: 1.6;
            white-space: pre-wrap;
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
            <h1>
                <svg style="width:24px;height:24px;vertical-align:middle;margin-right:8px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Yeni Rezervasyon Talebi
            </h1>
            <p class="header-subtitle">Bir müşteriden rezervasyon talebi aldınız</p>
        </div>

        <div class="content">
            <h3 class="section-title">
                <svg style="width:18px;height:18px;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Villa Bilgileri
            </h3>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Villa:
                    </span>
                    <span class="info-value">{{ $villa['baslik'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Fiyat:
                    </span>
                    <span class="info-value">{{ number_format($villa['fiyat']) }} TL</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        Konum:
                    </span>
                    <span class="info-value">{{ $villa['il'] }}@if($villa['ilce']), {{ $villa['ilce'] }}@endif</span>
                </div>
            </div>

            <h3 class="section-title">
                <svg style="width:18px;height:18px;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Müşteri Bilgileri
            </h3>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Ad Soyad:
                    </span>
                    <span class="info-value">{{ $booking['ad_soyad'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        Telefon:
                    </span>
                    <span class="info-value">{{ $booking['telefon'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        E-posta:
                    </span>
                    <span class="info-value">{{ $booking['email'] }}</span>
                </div>
            </div>

            <h3 class="section-title">
                <svg style="width:18px;height:18px;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Tarih & Kişi Bilgisi
            </h3>
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Giriş Tarihi:
                    </span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($booking['giristarihi'])->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Çıkış Tarihi:
                    </span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($booking['cikistarihi'])->format('d.m.Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Kişi Sayısı:
                    </span>
                    <span class="info-value">{{ $booking['kisi_sayisi'] }} kişi</span>
                </div>
            </div>

            @if(!empty($booking['mesaj']))
            <div class="message-box">
                <div class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    Müşteri Mesajı
                </div>
                <div class="text">{{ $booking['mesaj'] }}</div>
            </div>
            @endif
        </div>

        <div class="footer">
            © {{ date('Y') }} Yalıhan Emlak - Otomatik Bildirim Sistemi
        </div>
    </div>
</body>
</html>
