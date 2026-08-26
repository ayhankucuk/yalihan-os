# Görev: Admin UI ve production asset hardening

## Atanacak rol

Frontend + Laravel/DevOps agent. Antigravity üzerinde paralel analiz yapılabilir; uygulama değişikliği Kilo Code/VS Code üzerinden kontrollü diff ile yapılmalı.

## Amaç

`/admin/ilanlar/create` ve `/admin/property-hub` ekranlarını production’da çalışan, tutarlı ve doğrulanabilir hale getirmek.

## Bulgular

1. `/admin/ilanlar/create` canlıda açılıyor ancak ana CSS yüklenmiyor.
2. Dockerfile asset’leri image içinde `/app/public/build` altına üretiyor.
3. nginx compose, host `/opt/yalihan2026/current/public` dizinini `/app/public` üzerine mount ediyor; bu image asset’lerini gizleyebiliyor.
4. `/admin/property-hub` canlıda Laravel HTTP 500 döndürüyor.
5. Browser console’da `leaflet-draw.js: L is not defined` görüldü.

## Kabul kriterleri

- Asset mount/sync stratejisi açıkça belgelenmiş ve image asset’lerini gizlemiyor.
- `/build/assets/css/app-*.css` production’da HTTP 200 dönüyor.
- `/admin/ilanlar/create` desktop ve mobilde stilli render oluyor.
- Browser console’da blocking CSS/Leaflet hatası kalmıyor.
- `/admin/property-hub` HTTP 200 dönüyor ve dashboard render oluyor.
- Property Hub 500’ün gerçek exception’ı test veya log kanıtıyla açıklanıyor.
- Tenant isolation, auth/session ve migration davranışı değişmeden kalıyor.
- İlgili otomatik testler çalıştırılıyor ve tam çıktı kaydediliyor.
- VPS deploy edilirse commit, container health ve HTTP/browser sonuçları production ledger’a ekleniyor.
- İlan oluşturma veya taslak kaydetme yapılmıyor.

## Çalışma sırası

1. Local: compose, Dockerfile, Vite manifest ve nginx mount zincirini doğrula.
2. Local: PropertyHub DashboardController ve Orchestrator hata yolunu test et.
3. Local: Leaflet import/load order’ını düzelt; gereksiz global bağımlılık ekleme.
4. Değişiklikleri küçük diff’lere ayır ve test et.
5. Kullanıcı onayı olmadan production deploy yapma.
6. Deploy sonrası asset HTTP testi, Property Hub HTTP testi ve browser görsel testi yap.

## Rapor formatı

- Root cause
- Changed files
- Tests and complete results
- Local verification
- Production verification
- Remaining risks
- Recommended next task
