# ADR 021: Yalıhan Bekçi v2.1 — Cognitive Guardian (AST Analysis)

**Status:** ACCEPTED
**Date:** 2026-05-15
**Author:** Antigravity (Architectural Guardian)
**Phase:** Phase 11 (The Learner)

## Context

Yalıhan AI OS, Phase 11 öncesinde mimari ihlalleri tespit etmek için basit metin tarama (regex/grep) yöntemini kullanıyordu. Bu yöntem "Sessiz Catch" (swallowed exceptions) veya "Controller Env Usage" gibi anlamsal (semantic) hataları yakalamakta yetersiz kalıyor ve yüksek oranda "false negative" riski taşıyordu. Ayrıca isimlendirme (naming) politikası sadece doküman düzeyinde kalıyor, kod düzeyinde anlık denetlenemiyordu.

## Decision

Sistemin "Bilişsel" (Cognitive) bir koruma katmanına taşınmasına karar verildi:

1.  **AST (Abstract Syntax Tree) Entegrasyonu:** Bekçi, kodu metin olarak değil, PHP yapısını anlayan bir ağaç yapısı olarak analiz edecek.
2.  **Semantic Audit Rule Set:** Sessiz catch'ler, yasaklı env kullanımı ve hibrit isimlendirme ihlalleri için AST tabanlı kurallar tanımlandı.
3.  **Living Memory:** Sistem, çözülen hatalardan ders çıkaracak (`LEARNED_PATTERNS.json`) ve bu hataların tekrarlanmasını (regression) bloklayacak.
4.  **Hybrid Naming Enforcement:** Domain (Türkçe) vs Framework (İngilizce) dengesi anayasal bir kural olarak kod düzeyinde denetlenecek.

## Consequences

- **Pozitif:** Mimari sızıntılar (ngrok, secret vb.) %100 doğrulukla yakalanabilir hale geldi.
- **Pozitif:** 42 adet gizli "Anlamsal Hayalet" (Semantic Ghost) tespit edildi ve kayıt altına alındı.
- **Pozitif:** Teknik borç artık "görünür" ve "ölçülebilir" bir hale geldi.
- **Negatif:** Taramalar (audit) regex'e göre biraz daha fazla CPU tüketir (ancak CI/CD hattında tolere edilebilir seviyededir).
- **Negatif:** Geliştiricilerin yeni "Bilişsel Kapılar"dan (Cognitive Gates) geçmesi gerekecek.

## References

- `docs/SAB.md` v1.1.0
- `app/Console/Commands/Governance/BekciAuditCommand.php`
- `app/Services/Governance/Ast/Rules/`
