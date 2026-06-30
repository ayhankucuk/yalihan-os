# Architecture Documentation Index

> STATUS: REFERENCE ONLY — NOT SSOT
> Authority order: Human > Live Code > .sab/authority.json > this documentation

## Purpose

Bu klasör sistemin nasıl yapılandırıldığını açıklar:
- Domain tanımları ve sınırları
- Sayfa kataloğu ve sahiplik
- Model sahiplik haritası
- Servis sahiplik tablosu
- İş akışları ve riskler
- Governance sınırları
- Telemetri yüzeyleri

## Rules

- Bu klasörü **write authority** olarak kabul etmeyin
- Güncelleme ancak runtime/code doğrulamasından sonra yapılır
- Spekülatif iddia yasaktır
- Kod ile doküman çelişirse → **kod kazanır**

## Index

| Dosya | Ne Cevaplar |
|-------|------------|
| [domains.md](./domains.md) | Hangi domain var, sınırları ne? |
| [pages.md](./pages.md) | Bu URL ne sayfası, ne iş yapıyor? |
| [models.md](./models.md) | Bu model kimin, nerede kullanılıyor? |
| [flows.md](./flows.md) | Bu akış nasıl çalışıyor, risk nerede? |
| [side-menu-map.md](./side-menu-map.md) | Sidebar menüsü → alt sayfa → amaç |
| [service-ownership.md](./service-ownership.md) | Bu servis kimin, ne yapıyor? |
| [ui-to-route-map.md](./ui-to-route-map.md) | UI sayfa → route → controller → servis |
| [governance-boundaries.md](./governance-boundaries.md) | Governance ilkeleri ve tuzaklar |
| [telemetry-map.md](./telemetry-map.md) | Hangi dashboard neyi ölçüyor, false healthy riski |
| [open-questions.md](./open-questions.md) | Doğrulanmamış açık sorular |
| [automation-layer.md](./automation-layer.md) | Telegram, n8n, edge automation mimarisi |
| [ai-provider-strategy.md](./ai-provider-strategy.md) | DeepSeek-first strateji, provider fallback zinciri |
| [integration-contracts.md](./integration-contracts.md) | Webhook kontratları, payload şemaları |
| [security-boundaries.md](./security-boundaries.md) | Güvenlik perimetresi, açık gap'ler |

## Quick Reference

Tek dosya hızlı referans: [../architecture-lite.md](../architecture-lite.md)

## Last Updated
2026-05-15 — **Phase 11 (The Learner)** successfully deployed. Yalıhan Bekçi v2.1 (Cognitive Guardian) integrated with AST-based semantic analysis. Global Seal status elevated to `GLOBAL_SEAL_SUCCESS`. Phase 12 (Global Scaling) initialization.
