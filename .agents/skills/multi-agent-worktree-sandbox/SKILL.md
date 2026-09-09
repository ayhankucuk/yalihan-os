---
name: multi-agent-worktree-sandbox
description: Çoklu ajan geliştirmesinde Git worktree, test veritabanı, storage kanıtı ve staging sınırlarını izole eder; ajanlar arası diff ve kontrat sürüklenmesini engeller.
---

# Multi Agent Worktree Sandbox

Bu yetenek, Codex, Antigravity, Kilo veya yerel geliştirici aynı projede çalışırken kaynak kodu, test verisini ve kanıt dosyalarını birbirinden ayırır.

## Başlangıç sözleşmesi

Her görev başlamadan önce şu kayıtları al:

- aktif branch ve worktree yolu
- temiz/kirli Git durumu ve mevcut değişiklik sahibi
- görev kapsamındaki dosya listesi
- test DB yolu, storage yolu ve uygulama URL'si
- beklenen HTTP/API/form kontratı ve kanıt seviyesi

Ana checkout başka bir ajan tarafından kullanılıyorsa yazma yapma. Her yazan ajan kendi worktree'sinde ve kendi branch'inde çalışmalıdır.

## Test izolasyonu

- Aynı `database.sqlite` veya ortak MySQL test şemasını paralel testlerde kullanma.
- Test koşusu başına hash'li geçici DB/şema ve ayrı storage kökü kullan; Laravel config/cache'in gerçekten bu hedefi kullandığını koşudan önce doğrula.
- Fixture ID'lerini sabitleme. Oluşturulan kayıtların correlation ID'sini ve tenant'ını test çıktısında taşı.
- Test tamamlandığında geçici DB/storage'ı temizle veya kanıt amacıyla saklandığını açıkça belirt; ana repo storage'ına dosya bırakma.
- Bir koşu `422`, diğer koşu `200` bekliyorsa önce ortak API/form sözleşmesini belirle; iki sonucu da sessizce kabul eden assertion yazma.

## Staging ve commit sınırı

- Sadece görev kapsamındaki dosyaları stage et; `git diff --staged --name-only` listesini beklenen kapsamla birebir karşılaştır.
- Başka ajana ait değişiklik, evidence PNG/JSON, log veya storage dosyasını taşıma, silme veya stage etme.
- Commit öncesi `git diff --check` ve secret scan çalıştır. Migration/seed/deploy üretim yetkisi gerektiriyorsa `BLOCKED_PENDING_PRODUCTION_AUTH` olarak bırak.
- Worktree temizliği için `git restore -- <hedef-dosya>` veya `git checkout HEAD -- <hedef-dosya>` gibi yalnızca kapsamı kesin hedefleyen geri alma komutlarını kullan; `git reset --hard`, geniş `rm -rf` veya ortak DB silme yasaktır.

## Handoff ve kontrat mutabakatı

Handoff mesajı şu alanları içermeli: owner, worktree/branch, değişen dosyalar, commit, test komutu, ham sonuç yolu, bilinen blokaj ve sonraki doğrulama. Agent raporunu repository/test/browser/production kanıtı yerine koyma.

Bir ajan runtime kodunu, diğeri E2E fixture'ını değiştirirse önce aynı commit veya exact diff üzerinde yeniden çalıştır. "Kod tamamlandı" ile "test doğrulandı" ve "canlıya çıktı" iddialarını ayrı tut.

## Sınırlar

Bu yetenek ajanlar arasında koordinasyon ve izolasyon sağlar; kullanıcı adına production migration, seed, deploy, veri backfill veya silme yapmaz.
