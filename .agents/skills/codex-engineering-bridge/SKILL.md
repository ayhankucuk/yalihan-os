---
name: codex-engineering-bridge
description: Codex kredi/kota darboğazında mühendislik görevini devralır; /Documents/Codex ortak çalışma alanında şeffaf senkronizasyon sağlar ve Klio, Cline, Code, Wenox agentlarına görev dağıtır.
---

# Codex Engineering Bridge & Worktree Synchronization Skill

## 🎯 Amaç
OpenAI Codex veya yerel ChatGPT Codex istemcisi kredi/kota sınırına ulaştığında, mühendislik operasyonlarının kesintiye uğramaması için:
1. **Lider Mühendislik Devri:** Antigravity baş mimar ve icracı mühendis (Engineering Lead) sorumluluğunu üstlenir.
2. **Ortak Çalışma Alanı Senkronizasyonu (`/Users/macbookpro/Documents/Codex`):** 
   - Codex'in yerel çalışma mantığına tam uyumlu olarak günlük klasör altında (`YYYY-MM-DD/<session-name>/work/` ve `outputs/`) çalışılır.
   - Yapılan her analiz, kod değişikliği, test çıktısı ve devir teslim kaydı buraya yazılarak Codex'in ek kredi/token harcamadan yapılanları dosya sistemi üzerinden doğrudan görmesi sağlanır.
3. **Multi-Agent İş Bölümü ve Model Dağılımı:**
   - **Klio (Kilo) [`claude-opus-4-8`]:** Core Backend, Domain Mantığı, Eloquent & Yazma Otoritesi (`IlanCrudService`), SAB Anayasası, derin mimari ve PHPUnit Feature testleri. *(Ağır akıl yürütme ve mimari derinlik için Opus kullanılır).*
   - **Cline [`claude-sonnet-4.6`]:** Frontend & Blade Mimarisi, UI/UX (Premium Mediterranean Design System), Alpine.js durum yönetimi, Form & Dependency arayüzleri. *(Hızlı render, hassas UI ve token verimliliği için Sonnet kullanılır).*
   - **Code (Claude Code / Terminal Runner) [`claude-sonnet-4.6`]:** Terminal işlemleri, Linter (`composer lint`), Artisan komutları, CI/CD Scriptleri, Veri temizleme & migration kontrolleri.
   - **Wenox [`claude-sonnet-4.6`]:** Workflow & Event Entegrasyonu, Hermes Event Sync, n8n webhook'ları, Harici API (Airbnb iCal, PayTR, Gmail) koordinasyonu.
   - **Antigravity [`claude-opus-4-8` / IDE]:** Baş Mühendislik (Chief Engineer), mimari denetim, gate onayları, agent devir-teslim mutabakatı ve Codex köprüsü senkronizasyonu.

---

## 📂 Ortak Dizin Hiyerarşisi (`/Users/macbookpro/Documents/Codex`)

Her gün veya operasyon devrinde şu yapı işletilir:
```text
/Users/macbookpro/Documents/Codex/
  └── YYYY-MM-DD/
      └── antigravity-engineering-takeover/
          ├── TASK_DISPATCH.md       <-- Agent görev dağılım matrisi
          ├── ENGINEERING_LOG.md     <-- Yapılan mühendislik adımları ve çözümler
          ├── SHARED_STATE.md        <-- Ortak sistem durumu, kritik alarmlar
          ├── work/                  <-- Geçici çalışma dosyaları, scriptler, analizler
          └── outputs/               <-- Test raporları, render çıktıları, nihai diff'ler
```

---

## 🛑 Değişmez Kurallar

1. **Sıfır Token/Kredi İsrafı:** Codex'in kredisi bittiğinde Codex'e gereksiz prompt veya ağır LLM çağrıları yaptırılmaz. İhtiyaç duyulan tüm bağlam ve çıktı bu ortak dizine ve `.project-brain/` içine dosya olarak bırakılır.
2. **Multi-Agent Worktree İzolasyonu:** Klio, Cline, Code ve Wenox `multi-agent-conflict-guard` protokolüne uyar. Kilitli dosyalara dokunulmaz, çakışma durumunda `PROJECT_STATE.md` üzerinden handoff istenir.
3. **Kalıcı Hafıza Senkronizasyonu:** Otomasyon sonuçları `~/.codex/automations/takip-arac/memory.md` ve `docs/BEKCI_CHANGELOG.md` ile uyumlu tutulur.

---

## 📋 Görev Dağıtım Prosedürü (Task Dispatching)

Antigravity bir işi delege ederken şu adımları izler:
1. `TASK_DISPATCH.md` dosyasına görevi, sorumlu agent'ı, kilitlenen dosyaları ve kabul kriterlerini yazar.
2. İlgili agent görevi tamamlayıp kanıtını (`TEST_VERIFIED`, `REPO_VERIFIED`) sunduğunda durumu `COMPLETED` olarak günceller.
3. Günü kapatırken veya devir yaparken `ENGINEERING_LOG.md` özetini Codex dizinine işler.
