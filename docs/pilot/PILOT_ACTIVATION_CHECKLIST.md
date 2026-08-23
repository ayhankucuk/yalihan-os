# PILOT ACTIVATION CHECKLIST — GÜVENLİK ONTACAK KURALLAR

## ASLA YAPILMAYACAKLAR
- Secret/credential/token yazilmaz
- Allowlist genişletilmez
- Yeni intent eklenmez
- Test ortaminda production token kullanilmaz

## AKTIVASYON MATRISI
| Bileşen | Şimdi | Gerekli |
|---------|--------|---------|
| gc_enabled | FALSE | TRUE (pilot icin |
| kill_switch | FALSE | FALSE |
| pilot_gate.is_safe | FALSE | TRUE (1+1 ile |
| WhatsApp token | BOŞ | Üretim token |
| WhatsApp phone_id | BOŞ | Üretim ID |
| WhatsApp secret | BOŞ | Üretim secret |

## GÖREV SAHIBI TÜM DEGERLERI KENDİ YAZACAK

## TOKENLERİ HİÇBİR YERE YAZMIYORUM

## GÖREVİM BURADA TAMAMLANDI
