#!/usr/bin/env bash
# ==============================================================================
# Yalıhan OS — Advisory Documentation, SSOT & Link Health Auditor
# Mod: SALT-OKUNUR & ADVISORY (Kesinlikle blocking değildir, daima exit 0 döner)
# ==============================================================================

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📑 Yalıhan OS: Advisory Documentation, SSOT & Link Auditor"
echo "Mod: ADVISORY (Non-blocking — Raporlama Modu)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

WARNINGS=0

# 1. Dahili Worktree Tespiti ve Gerçek İzolasyon (Dışlama)
echo ""
echo "🔍 1. Dahili Worktree Tespiti ve Tarama İzolasyonu..."
INTERNAL_WORKTREES=$(find . -maxdepth 2 -type d \( -name "kilo-*" -o -name "worktrees" -o -name "yalihan-os.worktrees" -o -name ".kilo" \) -not -path "*/.git/*" 2>/dev/null || true)
if [ -n "$INTERNAL_WORKTREES" ]; then
    echo "  ⚠️ [ADVISORY] Repo içinde tespit edilen dahili worktree dizinleri:"
    for wt in $INTERNAL_WORKTREES; do
        echo "     • $wt"
    done
    echo "  🛡️ [İZOLASYON UYGULANDI] Aşağıdaki tüm kontrollerde bu dizinler budanmış (-prune) ve dışlanmıştır."
    WARNINGS=$((WARNINGS + 1))
else
    echo "  ✅ Dahili worktree kirliliği tespit edilmedi."
fi

# 2. Kök Dizin Markdown Kontrolü (Allowlist)
echo ""
echo "📂 2. Kök Dizin Serbest Markdown Kontrolü..."
ALLOWED_ROOT_MDS=("README.md" "AGENTS.md" "CONTRIBUTING.md" "CLAUDE.md" "ROADMAP.md")
ROOT_MDS=$(find . -maxdepth 1 -name "*.md" -exec basename {} \; 2>/dev/null || true)

for rmd in $ROOT_MDS; do
    is_allowed=false
    for allowed in "${ALLOWED_ROOT_MDS[@]}"; do
        if [ "$rmd" == "$allowed" ]; then
            is_allowed=true
            break
        fi
    done
    if [ "$is_allowed" = false ]; then
        echo "  ⚠️ [ADVISORY] Kök dizinde izinli liste dışında serbest .md dosyası: $rmd"
        WARNINGS=$((WARNINGS + 1))
    fi
done

# 3. Pilot Kanonik Belgelerde 10/10 Tam Şema Metadata (YAML Frontmatter) Kontrolü
echo ""
echo "🏷️ 3. Pilot Kanonik Belgelerde 10/10 Tam Şema Metadata Kontrolü..."
PILOT_FILES=(
    ".project-brain/DOCUMENTATION_LIFECYCLE_CONTRACT.md"
    ".project-brain/PROJECT_STATE.md"
    "docs/adr/2026-09-07-adr042-architecture-backbone-audit.md"
    "docs/architecture/tenant-isolation-audit-2026-09-06.md"
)

# 10 Zorunlu Şema Alanı:
# 1: document_id
# 2: document_owner veya owner
# 3: decision_owner
# 4: status
# 5: canonical
# 6: evidence_level
# 7: as_of_commit
# 8: last_reviewed
# 9: review_after
# 10: supersedes
SCHEMA_EXPLICIT_FIELDS=(
    "document_id"
    "decision_owner"
    "status"
    "canonical"
    "evidence_level"
    "as_of_commit"
    "last_reviewed"
    "review_after"
    "supersedes"
)

TODAY=$(date +%Y-%m-%d)
PILOT_METADATA_PASS=0
PILOT_METADATA_TOTAL=${#PILOT_FILES[@]}

for pfile in "${PILOT_FILES[@]}"; do
    if [ ! -f "$pfile" ]; then
        echo "  ❌ [HATA] Pilot dosya bulunamadı: $pfile"
        WARNINGS=$((WARNINGS + 1))
        continue
    fi

    # Frontmatter bloğunu çıkar (dosyanın başındaki ilk --- bloğu)
    frontmatter=$(awk '/^---$/{p++; if(p==1) next; if(p==2) exit} p==1{print}' "$pfile")
    if [ -z "$frontmatter" ]; then
        echo "  ⚠️ [ADVISORY] $pfile: YAML frontmatter bloğu bulunamadı!"
        WARNINGS=$((WARNINGS + 1))
        continue
    fi

    missing_fields=()
    verified_count=0

    # 1. document_owner / owner kontrolü
    if echo "$frontmatter" | grep -qE "^(document_owner|owner):"; then
        verified_count=$((verified_count + 1))
    else
        missing_fields+=("owner/document_owner")
    fi

    # 2-10. Kalan 9 zorunlu alanın kontrolü
    for field in "${SCHEMA_EXPLICIT_FIELDS[@]}"; do
        if echo "$frontmatter" | grep -qE "^${field}:"; then
            verified_count=$((verified_count + 1))
        else
            missing_fields+=("$field")
        fi
    done

    doc_id=$(echo "$frontmatter" | grep "^document_id:" | awk '{print $2}')
    as_of=$(echo "$frontmatter" | grep "^as_of_commit:" | awk '{print $2}')
    ev_level=$(echo "$frontmatter" | grep "^evidence_level:" | awk '{print $2}')
    review_after=$(echo "$frontmatter" | grep "^review_after:" | awk '{print $2}')

    if [ ${#missing_fields[@]} -gt 0 ]; then
        echo "  ⚠️ [ADVISORY] $pfile [ID: ${doc_id:-YOK}]: Eksik alanlar ($verified_count/10): ${missing_fields[*]}"
        WARNINGS=$((WARNINGS + 1))
    else
        echo "  ✅ $pfile [10/10 alan tam]"
        echo "     └─ ID: $doc_id | Commit: $as_of | Evidence: $ev_level | Review After: $review_after"
        PILOT_METADATA_PASS=$((PILOT_METADATA_PASS + 1))
    fi

    # Süre aşımı kontrolü
    if [ -n "$review_after" ] && [[ "$review_after" < "$TODAY" ]]; then
        echo "     ⚠️ [ADVISORY] STALE_REVIEW_REQUIRED: Son inceleme tarihi ($review_after) geçmiş!"
        WARNINGS=$((WARNINGS + 1))
    fi
done

# 4. Gerçek Dahili Link & Referans Doğrulaması (Broken-Link Auditor)
echo ""
echo "🔗 4. Dahili Bağlantı ve Referans Sağlığı (Broken-Link Check)..."
LINK_AUDIT_OUTPUT=$(python3 -c '
import re, os, sys

ROOT_DIR = os.getcwd()
pilot_files = [
    ".project-brain/DOCUMENTATION_LIFECYCLE_CONTRACT.md",
    ".project-brain/PROJECT_STATE.md",
    "docs/adr/2026-09-07-adr042-architecture-backbone-audit.md",
    "docs/architecture/tenant-isolation-audit-2026-09-06.md"
]

total_links = 0
broken = []

def to_github_slug(header_text):
    t = re.sub(r"^#+\s*", "", header_text).strip().lower()
    t = re.sub(r"[^\w\s-]", "", t)
    return re.sub(r"\s", "-", t)

def get_heading_slugs(raw_lines):
    slug_counts = {}
    slugs = set()
    for l in raw_lines:
        if l.strip().startswith("#"):
            base_slug = to_github_slug(l)
            if not base_slug:
                continue
            if base_slug in slug_counts:
                slug_counts[base_slug] += 1
                slug = f"{base_slug}-{slug_counts[base_slug]}"
            else:
                slug_counts[base_slug] = 0
                slug = base_slug
            slugs.add(slug)
    return slugs

for pf in pilot_files:
    abs_pf = os.path.join(ROOT_DIR, pf)
    if not os.path.exists(abs_pf):
        continue
    with open(abs_pf, "r", encoding="utf-8", errors="ignore") as f:
        lines = f.readlines()
    
    current_doc_slugs = get_heading_slugs(lines)
    
    in_code = False
    for line_idx, line in enumerate(lines, 1):
        stripped = line.strip()
        if stripped.startswith("```"):
            in_code = not in_code
            continue
        if in_code:
            continue
        
        matches = re.findall(r"\[([^\]]+)\]\(([^)]+)\)", line)
        for text, target in matches:
            if target.startswith("http://") or target.startswith("https://") or target.startswith("mailto:"):
                continue
            total_links += 1
            if target.startswith("#"):
                # Belge içi çapa linki (kesin GitHub markdown slug eşleşmesi)
                anchor = target[1:]
                if anchor not in current_doc_slugs:
                    broken.append(f"{pf}:{line_idx} - Geçersiz çapa linki: {target}")
            else:
                # Dosya yolu linki ve olası harici çapa kontrolü
                parts = target.split("#")
                clean_target = parts[0].replace("file://", "")
                anchor = parts[1] if len(parts) > 1 else None
                
                if clean_target.startswith("/"):
                    target_abs = clean_target
                else:
                    target_abs = os.path.normpath(os.path.join(os.path.dirname(abs_pf), clean_target))
                
                if not os.path.exists(target_abs):
                    broken.append(f"{pf}:{line_idx} - Dosya bulunamadı: {target} (Hedef: {target_abs})")
                elif anchor and os.path.isfile(target_abs):
                    with open(target_abs, "r", encoding="utf-8", errors="ignore") as tf:
                        target_slugs = get_heading_slugs(tf.readlines())
                    if anchor not in target_slugs:
                        broken.append(f"{pf}:{line_idx} - Hedef dosyada çapa bulunamadı: #{anchor} ({target})")

print(f"TOTAL={total_links}")
print(f"BROKEN_COUNT={len(broken)}")
for b in broken:
    print(f"ERR={b}")
')

TOTAL_LINKS_CHECKED=$(echo "$LINK_AUDIT_OUTPUT" | grep "^TOTAL=" | cut -d'=' -f2)
BROKEN_COUNT=$(echo "$LINK_AUDIT_OUTPUT" | grep "^BROKEN_COUNT=" | cut -d'=' -f2)

if [ "$BROKEN_COUNT" -eq 0 ]; then
    echo "  ✅ Pilot belgelerdeki $TOTAL_LINKS_CHECKED adet dahili bağlantı doğrulandı (0 bozuk link)."
else
    echo "  ⚠️ [ADVISORY] $BROKEN_COUNT adet bozuk bağlantı tespit edildi:"
    echo "$LINK_AUDIT_OUTPUT" | grep "^ERR=" | sed 's/ERR=/     • /'
    WARNINGS=$((WARNINGS + BROKEN_COUNT))
fi

# 5. İzole Edilmiş Aktif Dokümantasyon Sayımı (Worktree'ler Hariç)
echo ""
echo "📊 5. İzole Edilmiş Aktif Dokümantasyon Sayımı (Worktree'ler Hariç)..."
ACTIVE_MD_COUNT=$(find . \
    -type d \( -name ".git" -o -name "kilo-*" -o -name "worktrees" -o -name "yalihan-os.worktrees" -o -name ".kilo" -o -name "node_modules" -o -name "vendor" \) -prune \
    -o -type f -name "*.md" -print | wc -l | tr -d ' ')
echo "  ℹ️ Aktif izole repo doküman sayısı: $ACTIVE_MD_COUNT (Harici/dahili worktree kopyaları hariç)"

# 6. Belge İçi Çelişki / Split-Brain İfade Kontrolü
echo ""
echo "⚡ 6. Belge İçi Çelişki / Split-Brain İfade Kontrolü..."
PILOT_CONFLICT_SCAN=(
    ".project-brain/PROJECT_STATE.md"
    "docs/ERA_V/PHASE2-ROADMAP.md"
)

for cfile in "${PILOT_CONFLICT_SCAN[@]}"; do
    if [ -f "$cfile" ]; then
        has_completed=$(grep -E "\b(COMPLETED|TEST_VERIFIED)\b" "$cfile" 2>/dev/null | grep -c . || true)
        has_blocked=$(grep -E "\b(BLOCKED|HOLD)\b" "$cfile" 2>/dev/null | grep -c . || true)
        if [ "$has_completed" -gt 0 ] && [ "$has_blocked" -gt 0 ]; then
            echo "  ℹ️ $cfile içinde hem tamamlanmış ($has_completed) hem de bekleyen/blokeli ($has_blocked) maddeler var (Normal durum)."
        fi
    fi
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 AUDIT RAPORU SONUCU VE KAPSAM AYRIMI:"
echo "  • Pilot Metadata Uyumu      : $PILOT_METADATA_PASS / $PILOT_METADATA_TOTAL pilot dosyada 10/10 zorunlu alan doğrulandı"
echo "  • Pilot Link Doğrulaması    : $TOTAL_LINKS_CHECKED bağlantı kontrol edildi, $BROKEN_COUNT bozuk link"
echo "  • Genel Depo Advisory Durumu: $WARNINGS adet tavsiye uyarısı (kök serbest md, worktree tespiti)"
echo "  • İcra Modu: ADVISORY (Daima EXIT 0 — CI ve kod akışını engellemez)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

exit 0
