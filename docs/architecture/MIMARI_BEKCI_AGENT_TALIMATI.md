# YALIHAN Mimari Bekçi — ortak ajan okuma talimatı

Hedef: Codex, Kilo Code, Cline, Antigravity ve Wenox aynı onaylı rehberi kullansın. Bu belge bir otomatik senkronizasyon servisi değildir.

## Her yeni görevde

1. Çalıştığın Git kökünü, branch, HEAD ve dirty durumunu belirle. Başka ajanın dosyalarını değiştirme.
2. Bu checkout'taki `.agents/skills/yalihan-constitution-review/SKILL.md` yolunun varlığını ve göreve uygunluğunu kontrol et. Mimari, ADR, yetki/veri sınırı veya uyumluluk işinde dosyayı tamamen oku.
3. Önceki bulgu, yeniden doğrulama veya kapanış teslimi varsa aynı skill klasöründeki `references/finding-lifecycle.md` dosyasını tamamen oku. Diğer işlerde ilgisiz referansları yükleme.
4. Görevde belirtilen onaylı skill snapshot'ıyla karşılaştır. Sürüm etiketi tek başına yeterli değildir; SKILL.md ve kullanılan referansın SHA-256 değerlerini kontrol et. Ana checkout'taki untracked içerik kendiliğinden release edilmiş sürüm değildir.
5. Rehberin dört adımını yalnız ilgili kapsamda uygula. Mimari Bekçi test, MCP veya production onayının yerine geçmez.

## Ajanın kısa başlangıç bildirimi

`Ajan: … | Worktree: … | Branch/HEAD: … | Dirty: … | Skill yolu/sürümü: … | İçerik hashleri: … | Kapsam: …`

Hash kontrolü için macOS'ta, kendi Git kökünde:

```sh
shasum -a 256 .agents/skills/yalihan-constitution-review/SKILL.md .agents/skills/yalihan-constitution-review/references/finding-lifecycle.md
```

Dosyaları okumadan “okudum” deme. Hash eşitliği kullanım kanıtı değildir. Handoff'ta bulgu ID, incelenen snapshot, test sonucu ve açık ölçütleri taşı.

## Hangi ajan nasıl okur?

| Ajan | Proje yönergesindeki bağlantı | Yüklenmiyorsa |
|---|---|---|
| Codex | Kök AGENTS.md → bu talimat → skill | Görev mesajından bu talimatı açıkça okut |
| Cline | .clinerules → bu talimat → skill | Rules panelinde proje yönergesini etkinleştir; aynı görevde açık okuma iste |
| Kilo Code | .kilocode/rules/yalihan-mimari-bekci.md → bu talimat → skill | Kullandığı sürümün Rules ayarından projeye bağla veya açık okuma iste |
| Antigravity | GEMINI.md → bu talimat → skill | Host proje yönergesini otomatik almıyorsa görev mesajında dosyayı okut |
| Wenox / diğer | Kullandığı hostun proje yönergesi → bu talimat | Aynı açık okuma talimatını kullan |

Bu yollar repo tarafındaki bağlantılardır; her IDE sürümünün otomatik yüklediği iddiası değildir. Arayüz ve host davranışı görülmeden otomatik yükleme doğrulandı deme.

## Güncelleme ve eski worktree

- Bakım kaynağı repo skill klasörüdür. Global kopya varsa bu görev için repo kaynağına yönel; sessizce eski global metni kullanma.
- Yeni sürüm review ve commit sonrası belirlenen branch'lere normal entegrasyonla taşınır. Otomatik pull/merge, global dosya üzerine yazma veya dirty worktree senkronizasyonu yapma.
- Fark varsa ilgili rehberin onaylı kaynağını belirle. Başka checkout'taki onaylı rehberi salt-okunur okumak mümkündür; rehber kaynağını ve uygulama snapshot'ını ayrı bildir. Başka checkout'ın vendor/cache/DB'sini kullanma.
- Açık oturum eski yönergeyi tutabilir. Sonraki ilgili görevde dosyaları yeniden okut; yeni oturum veya hostun desteklediği reload gerekebilir. Yeniden okuma gözlenmeden güncellendi deme.
- Tüm worktree'lere kopya dağıtma. Tek bakım kaynağı + açık kaynak seçimi kullan.

## Benimsenme kontrolü

Önce `CONFIGURED`: dosya ve bağlantı mevcut. Sonra ajan gerçek dar görevde rehberi okur, doğru snapshot ve kanıtla sonuç verir; ancak bu gözlem `ADOPTION_VERIFIED` olur. Kilo/Cline/Antigravity için ayrı doğrulanır.

Sonucu yeni bir takip sistemi açmadan mevcut EVIDENCE_INDEX veya ilgili bulgu kaydına yaz: ajan, zaman, kaynak hashleri, görev/snapshot, gözlenen davranış ve eksik kontrol. Bir ajanın PASS mesajı diğerlerini doğrulamaz.

## Ajanlara gönderilecek başlangıç mesajı

“Kendi checkout'ında docs/architecture/MIMARI_BEKCI_AGENT_TALIMATI.md dosyasını oku. YALIHAN Mimari Bekçi'nin göreve uygun kaynak sürümünü belirle; ilgili skill ve gerekiyorsa yaşam döngüsü rehberini tamamen oku. Yol, sürüm/hash ve çalışma snapshot'ını kısa bildir. Yalnız atanmış görevi yap; production veya senkronizasyon yetkisi çıkarma.”
