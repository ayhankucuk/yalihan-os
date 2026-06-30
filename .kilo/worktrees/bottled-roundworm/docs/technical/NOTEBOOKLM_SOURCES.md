# 📚 NotebookLM Source Dosyaları

## Temiz Notebook için Eklenecek Dosyalar

### 🏛️ Governance & SAB (Öncelik: Yüksek)

1. **SAB.md** - Sistem Anayasası Belgesi
   - Path: `docs/SAB.md`
   - Açıklama: Projenin bağlayıcı teknik anayasası, tüm kurallar

2. **CLAUDE_MEMORY.md** - Roo'nun Hafızası
   - Path: `docs/governance/CLAUDE_MEMORY.md`
   - Açıklama: Roo'nun öğrendiği pattern'lar, kararlar, context

3. **LEARNED_PATTERNS.json** - Öğrenilen Pattern'lar
   - Path: `docs/governance/LEARNED_PATTERNS.json`
   - Açıklama: LP-001 ile LP-XXX arası tüm pattern'lar

4. **BEKCI_CHANGELOG.md** - Bekçi Değişiklikleri
   - Path: `docs/BEKCI_CHANGELOG.md`
   - Açıklama: Yalıhan Bekçi'nin version history'si

### 🤖 AI & Collaboration (Öncelik: Yüksek)

5. **ROO_CAPABILITIES.md** - Roo'nun Yetenekleri
   - Path: `docs/ROO_CAPABILITIES.md`
   - Açıklama: Roo'nun ne yapabildiği, sınırları

6. **AI_COLLABORATION_DESIGN.md** - AI İşbirliği Tasarımı
   - Path: `docs/AI_COLLABORATION_DESIGN.md`
   - Açıklama: Roo + Engineer işbirliği mimarisi

7. **GEMINI_ENGINEER_PLAN.md** - Gemini Engineer Planı
   - Path: `docs/GEMINI_ENGINEER_PLAN.md`
   - Açıklama: NotebookLM entegrasyon planı

8. **NOTEBOOKLM_INTEGRATION.md** - NotebookLM Entegrasyonu
   - Path: `docs/NOTEBOOKLM_INTEGRATION.md`
   - Açıklama: Bu entegrasyonun dokümantasyonu

### 🏗️ Architecture (Öncelik: Orta)

9. **README.md** - Proje Genel Bakış
   - Path: `README.md`
   - Açıklama: Projenin genel tanıtımı

10. **CONTRIBUTING.md** - Katkı Rehberi
    - Path: `CONTRIBUTING.md`
    - Açıklama: Projeye nasıl katkı yapılır

### 📝 Kod Örnekleri (Öncelik: Orta)

11. **KisiRepository.php** - Repository Pattern Örneği
    - Path: `app/Repositories/KisiRepository.php`
    - Açıklama: Ownership scope, soft delete, best practices

12. **GorevRepository.php** - Repository Pattern Örneği
    - Path: `app/Repositories/GorevRepository.php`
    - Açıklama: Multi-tenant scoping örneği

13. **CRMScopedDeleteSafetyTest.php** - Test Örneği
    - Path: `tests/Unit/Repositories/CRMScopedDeleteSafetyTest.php`
    - Açıklama: Ownership scope test pattern'i

14. **ForbiddenFunctionAstRule.php** - AST Rule Örneği
    - Path: `app/Services/Governance/Ast/Rules/ForbiddenFunctionAstRule.php`
    - Açıklama: Bekçi AST kuralı örneği

### 🔧 Configuration (Öncelik: Düşük)

15. **composer.json** - Dependency Management
    - Path: `composer.json`
    - Açıklama: PHP dependencies, scripts

16. **package.json** - Frontend Dependencies
    - Path: `package.json`
    - Açıklama: Node.js dependencies

---

## 📋 Ekleme Sırası (Önerilen)

### Phase 1: Core Governance (Mutlaka Ekle)
1. SAB.md
2. CLAUDE_MEMORY.md
3. LEARNED_PATTERNS.json
4. ROO_CAPABILITIES.md

### Phase 2: AI Collaboration (Mutlaka Ekle)
5. AI_COLLABORATION_DESIGN.md
6. GEMINI_ENGINEER_PLAN.md
7. NOTEBOOKLM_INTEGRATION.md
8. BEKCI_CHANGELOG.md

### Phase 3: Architecture & Examples (Opsiyonel)
9. README.md
10. CONTRIBUTING.md
11. KisiRepository.php
12. GorevRepository.php
13. CRMScopedDeleteSafetyTest.php
14. ForbiddenFunctionAstRule.php

### Phase 4: Configuration (Opsiyonel)
15. composer.json
16. package.json

---

## 🎯 Minimum Viable Notebook

Eğer hızlı başlamak istersen, sadece **Phase 1 + Phase 2** (8 dosya) yeterli:

1. ✅ SAB.md
2. ✅ CLAUDE_MEMORY.md
3. ✅ LEARNED_PATTERNS.json
4. ✅ ROO_CAPABILITIES.md
5. ✅ AI_COLLABORATION_DESIGN.md
6. ✅ GEMINI_ENGINEER_PLAN.md
7. ✅ NOTEBOOKLM_INTEGRATION.md
8. ✅ BEKCI_CHANGELOG.md

Bu 8 dosya ile:
- ✅ SAB kurallarını sorgulayabilirsin
- ✅ Learned pattern'ları hatırlayabilirsin
- ✅ Roo'nun yeteneklerini bilebilirsin
- ✅ AI collaboration workflow'u anlayabilirsin

---

## 📝 Manuel Ekleme Adımları

1. **NotebookLM'e git:**
   https://notebooklm.google.com/notebook/317f976e-6e6a-47e9-97c5-c4ca4f8ecae5

2. **Mevcut source'ları sil:**
   - Her source'un yanındaki "..." → "Remove source"
   - Veya toplu silme varsa kullan

3. **Yeni source'ları ekle:**
   - "Add source" → "Upload file" veya "Paste text"
   - Yukarıdaki listeden dosyaları ekle

4. **Organize et:**
   - Source'lara açıklayıcı isimler ver
   - Kategorilere ayır (Governance, AI, Architecture, Code)

---

## 🚀 Hızlı Başlangıç

```bash
# 1. Dosyaları hazırla
cd /Users/macbookpro/dev/yalihan2026

# 2. Phase 1 + Phase 2 dosyalarını kopyala
# (Manuel olarak NotebookLM'e upload et)

# 3. Test et
# MCP tool: ask_question
{
  "question": "SAB'ın temel kuralları nelerdir?",
  "source_format": "footnotes"
}
```

---

**Not:** NotebookLM MCP Server şu anda `add_source` tool'u sadece `type=url` ve `type=text` destekliyor. Dosya upload'u için NotebookLM web UI kullanman gerekiyor.
