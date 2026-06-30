# ADR: Controller Mutation Delegation (Batch 5)

## Context
SAB integrity-scan çıktısında controller katmanında yeni HIGH seviye ihlaller tespit edildi. Özellikle `BlogController` içinde doğrudan model mutasyonları (`update/delete`) ve `HealthController` içinde hardcoded state string kullanımı, mimari kuralları ihlal ederek quality gate riskini artırdı.

## Decision
- `BlogController` içindeki doğrudan model mutasyonları action katmanına delege edildi.
- Bu amaçla `app/Actions/Admin/Blog` altında kategori, etiket ve yazı için güncelleme/silme/toggle/publish-feature-stick işlemleri için action sınıfları eklendi.
- `HealthController` içindeki hardcoded sağlık durumları sınıf sabitlerine taşındı.
- `IlanPublishController` üzerindeki comment kaynaklı yanlış-pozitif mutation pattern ifadesi, semantiği koruyacak şekilde yeniden yazıldı.
- `ArsaCalculatorController` içinde satır uzunluğu ihlalleri için ifadeler çok satırlı hale getirildi.

## Consequences
- Controller katmanında mutasyonlar tekil action sınıflarında toplanarak SAB C3 uyumu güçlendi.
- Kod okunabilirliği ve test edilebilirlik arttı.
- HIGH seviye yeni ihlaller temizlendi; yalnızca LOW seviye satır uzunluğu kozmetik kalemleri kaldı.
- Action sınıfı sayısı arttı; ancak sorumluluk ayrımı netleşti.

## Alternatives Considered
1. Controller içinde mutasyonları bırakmak
   - Reddedildi: SAB kural ihlali üretmeye devam eder.
2. Servis katmanına toplu taşıma
   - Kısmen uygun, ancak mevcut kod tabanında benzer küçük mutasyon operasyonları için action deseni daha tutarlı.
3. SAB kuralını gevşetmek
   - Reddedildi: Mimari yönetim hedefleri ve quality gate disiplini ile çelişir.
