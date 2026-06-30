#!/bin/bash
# Bugünkü tüm değişiklikleri commit et
# Çalıştır: bash docs/_commit.sh

set -e

cd "$(dirname "$0")/.."

# Önce lock varsa kaldır
rm -f .git/index.lock

# Tüm değişiklikleri stage et
git add -A

# Commit
git commit -m "feat(owner-portal): Task #14-20 + Phase 15-16 security + FIX-01/02 dead code

Owner Portal (D16):
- OwnerAuth: magic-link, plain token log açığı kapatıldı
- Task #19 Raporlar: export API bağlandı, filtre formu, gerçek durum göstergesi
- Task #20 UI/UX: mobil hamburger menü, dark mode, Toast, max-width düzeltmesi

Security Hardening:
- Phase 15: CSP nonce-based strict-dynamic
- Phase 16: danisman_id filtreleri admin-only guard

Dead Code:
- FIX-01: deprecated IlanController import silindi (routes/api/v1/admin.php)
- FIX-02: EtiketController + SAB PURGE UpsTemplateManager dead block kaldırıldı

Docs:
- docs/ROADMAP.md oluşturuldu
- docs/yalihan-project-brain-v3.md (Mayıs 2026 güncel)
- docs/_archived/yalihan-project-brain-v2.md arşivlendi

SAB v6.1.1 | CI: 724/813 | Bekçi herzaman uyanık"

echo ""
echo "✅ Commit tamamlandı."
echo ""
echo "Sıradaki adım:"
echo "  php artisan migrate   # Global Seal için MySQL bağlıyken çalıştır"
