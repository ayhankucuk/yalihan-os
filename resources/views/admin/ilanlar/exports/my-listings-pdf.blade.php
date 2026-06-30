<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>İlanlarım - PDF Raporu</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 22px;
        }
        .header .info {
            color: #666;
            margin-top: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th {
            background-color: #2563eb;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        .table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        .table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>İlanlarım - PDF Raporu</h1>
        <div class="info">
            <p><strong>Danışman:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Toplam İlan:</strong> {{ $listings->count() }} adet</p>
            <p><strong>Oluşturulma Tarihi:</strong> {{ $tarih }}</p>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Referans No</th>
                <th>Başlık</th>
                <th>Kategori</th>
                <th>İl / İlçe</th>
                <th>Fiyat</th>
                <th>Durum</th>
                <th>Görüntülenme</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($listings as $listing)
            <tr>
                <td>{{ $listing->id }}</td>
                <td>{{ $listing->referans_no ?? '-' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($listing->baslik ?? 'Başlıksız', 30) }}</td>
                <td>{{ $listing->altKategori?->name ?? $listing->anaKategori?->name ?? '-' }}</td>
                <td>{{ $listing->il?->il_adi ?? '-' }} / {{ $listing->ilce?->ilce_adi ?? '-' }}</td>
                <td>{{ number_format($listing->fiyat ?? 0, 0) }} {{ $listing->para_birimi ?? 'TL' }}</td>
                <td>{{ $listing->yayin_durumu ?? 'Aktif' }}</td>
                <td>{{ $listing->goruntulenme ?? 0 }}</td>
                <td>{{ $listing->created_at?->format('d.m.Y') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Bu rapor Yalıhan Emlak sistemi tarafından otomatik olarak oluşturulmuştur.</p>
        <p>{{ $tarih }}</p>
    </div>
</body>
</html>
