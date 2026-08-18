# Guest Concierge — Micro Pilot Activation Report

**Tarih:** 2026-08-16 13:53
**Agent:** Kilo (Claude Sonnet 4.6)
**Baseline:** `ae4c6fc`
**SAAB Authorization:** c7bb116 + Antigravity adversarial verification

---

## Sistem Durumu Özeti

| Bileşen | Durum | Not |
|---------|--------|-----|
| Kod repository | ✅ | 54 PASS, 130 assertion |
| Migration: guest_messages | ✅ | Ran on this system |
| Migration: gorevler operational fields | ✅ | Batch 46 |
| Kill Switch logic | ✅ | Verified ON=BLOCK, OFF=ALLOWS |
| PILOT-GATE-01 | ✅ | Deterministic PHP gate |
| PILOT-GATE-02 | ✅ | ollama/deepseek/openai |
| PILOT-GATE-03 | ✅ | concierge queue |
| WhatsApp webhook token | ⚠️ | Production ortamda ayarlanmalı |
| WhatsApp phone_id | ⚠️ | Production ortamda ayarlanmalı |
| Real guest phones | ⚠️ | Test veritabanında yok |
| LLM (ollama) | ⚠️ | localhost:11434 — local only |
| LLM (deepseek/openai key) | ⚠️ | Production'da ayarlanmalı |

---

## Pilot Aktivasyon Kriterleri

### Çevresel Kurulum (bu ortamda — KONTROL EDİLMELİ)

```
ENV VAR                  MEVCUT              GEREKLİ (PRODUCTION)
──────────────────────────────────────────────────────────────
GUEST_CONCIERGE_ENABLED  FALSE                TRUE (pilot için)
GUEST_CONCIERGE_KILL_SWITCH  FALSE              FALSE
GUEST_CONCIERGE_PILOT_TENANT_IDS  ""              1 (pilot tenant ID)
GUEST_CONCIERGE_PILOT_RESERVATION_IDS  ""          1 (pilot reservation ID)
CONCIERGE_LLM_PROVIDER  ollama              ollama/deepseek/openai
CONCIERGE_LLM_OLLAMA_URL   localhost:11434      üretim URL
CONCIERGE_LLM_DEEPSEEK_KEY  ""                API key
WHATSAPP_WEBHOOK_TOKEN   ""                Meta app token
WHATSAPP_PHONE_NUMBER_ID ""                WhatsApp Business ID
WHATSAPP_APP_SECRET     ""                Meta app secret
```

### Kod + Test (54 PASS — GÖSTERİLDİ)

```
Test Suite                          Sonuç
───────────────────────────────────────────────
GuestConciergePhase1Test 38 PASS  ✅
GuestConciergePilotReadinessTest 16 PASS ✅
Total                              54 PASS / 130 assertion
```

### Kill Switch Mantığı (SIMULE EDİLDİ)

```
Konfigürasyon                    Sonuç
─────────────────────────────────────────
enabled=F, kill_switch=F          BLOCKS  ✅
enabled=T, kill_switch=T          BLOCKS  ✅ (acil durdurma çalışıyor)
enabled=T, kill_switch=F          ALLOWS  ✅
```

---

## Pilot Kapsamı (DONduruldu)

```
TENANT:     1 tenant (ID T.BELİRLENECEK)
RESERVATION: 1 rezervasyon (ID T.BELİRLENECEK)
INTENT:     7 intent (WIFI_INFO, CHECK_IN/OUT, PARKING, HOUSE_RULES, TECHNICAL_ISSUE, CLEANING_REQUEST)
KILL-SWITCH: HER ZAMAN ÇALIŞIR DURUMDA
ALLOWLIST:  EXACTLY 1 tenant + 1 reservation
```

---

## Pilot Başarı Kriterleri

| KPI | Hedef | Ölçüm Yöntemi |
|-----|-------|----------------|
| tenant_escape | 0 | Audit log |
| unauthorized_action | 0 | GuestMessage audit |
| duplicate_action | 0 | Gorev idempotency log |
| wrong_auto_answer | 0 | Intent classification log |
| kill_switch_ready | TRUE | Kill switch simulation |
| automation_rate | hedef TBD | GuestMessage.response_mode=ANSWER+ACTION |
| escalation_rate | hedef TBD | GuestMessage.response_mode=ESCALATE |
| human_intervention_rate | hedef TBD | Escalation audit |

---

## Sonraki Adımlar

1. **Production ortamda** WhatsApp webhook token + phone_id konfigüre et
2. **Pilot tenant + reservation ID'leri** belirle ve env'e yaz
3. **Pilot tenant'a ait gerçek guest phone** ile WhatsApp test mesajı gönder
4. **Webhook → Router → Hermes → Policy → Response** pipeline log'larını doğrula
5. **GuestMessage tablosunda** audit kaydını kontrol et
6. **PILOT-GATE-01 invariant** korunduğunu doğrula (allowlist dışı misafir BLOCKED)

---

## Pilot Genişletilmemesi Kuralları

- Allowlist pilot süresince GENİŞLETİLMEZ
- Intent seti Phase 1 ile SINIRLI
- Yeni intent eklenmez
- Tenant/Reservation başka sistem değeri DEĞİL
