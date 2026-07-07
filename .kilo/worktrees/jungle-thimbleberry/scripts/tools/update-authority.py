#!/usr/bin/env python3
"""P2-F Ghost Migration SSOT + P0-A/B field contract update for authority.json"""
import json
import sys
import os

os.chdir('/Users/macbookpro/Projects/yalihanai')

with open('.sab/authority.json', 'r') as f:
    data = json.load(f)

# P2-F: Ghost migration canonicalization
data['migration_canonicalization'] = {
    '_directive': 'P2-F Ghost DNA prevention — aktif vs .disabled cakisma sifirlandi',
    '_generated': '2026-02-22',
    'canonical_set': '2026-xx-xx + 2025-10-xx active migrations are canonical.',
    'disabled_set': 'database/migrations/*.disabled are archived (non-canonical, NEVER re-enable).',
    'conflict_tables': {
        'leads': {
            'canonical': '2026_01_12_083349_create_leads_table.php',
            'disabled': '2025_12_31_220000_create_leads_table_context7.php.disabled'
        },
        'features': {
            'canonical': '2025_10_15_172758_create_features_table.php',
            'disabled': '2025_11_02_000001_create_polymorphic_features_system.php.disabled'
        },
        'feature_assignments': {
            'canonical': '2025_11_05_000001_create_feature_assignments_table.php',
            'disabled': '2025_11_02_000001_create_polymorphic_features_system.php.disabled'
        },
        'ai_feature_usages': {
            'canonical': '2026_01_16_210453_create_ai_feature_usages_table.php',
            'disabled': '2025_12_19_221834_create_ai_feature_usages_table.php.disabled'
        },
        'notifications': {
            'canonical': '2026_01_12_000001_create_notifications_table.php',
            'disabled': '2025_11_24_205259_create_notifications_table.php.disabled'
        },
        'admin_notifications': {
            'canonical': '2026_01_12_000001_create_notifications_table.php',
            'disabled': '2025_12_21_065535_create_admin_notifications_table.php.disabled'
        },
        'opportunities': {
            'canonical': '2026_01_13_090439_create_opportunities_table.php',
            'disabled': '2025_12_31_203446_create_opportunities_table.php.disabled'
        },
        'ref_sequences': {
            'canonical': '2026_01_13_151140_create_ref_sequences_table.php',
            'disabled': '2025_11_05_140000_create_ref_sequences_table.php.disabled'
        },
        'ilan_price_history': {
            'canonical': '2026_01_17_120632_create_ilan_price_history_table.php',
            'disabled': '2025_11_08_142309_create_ilan_price_history_table.php.disabled'
        },
        'ilan_goruntulenme_gunluk': {
            'canonical': '2026_01_13_081140_create_ilan_goruntulenme_gunluk_table.php',
            'disabled': '2025_11_19_160001_create_ilan_goruntulenme_gunluk_table.php.disabled'
        }
    },
    'ci_guard': 'scripts/ci-guard-ghost-migration.sh'
}

# P0-A + P0-B: Field contracts
if 'field_contracts' not in data:
    data['field_contracts'] = {}

data['field_contracts']['leads.crm_durumu'] = {
    'type': 'tinyint',
    'range': '0-4',
    'nullable': False,
    'canonical_name': 'crm_durumu',
    'enum_map': {'0': 'new', '1': 'reached', '2': 'qualified', '3': 'lost', '4': 'won'},
    'migration': '2026_01_12_083349_create_leads_table.php',
    'guard': 'Lead::setCrmDurumuAttribute — string to int FAIL-FAST setter',
    'string_write': 'FORBIDDEN — only Lead::CRM_* constants or mapped int'
}

data['field_contracts']['ai_logs.aktiflik_kodu'] = {
    'type': 'integer',
    'canonical_name': 'aktiflik_kodu',
    'deprecated_name': 'status_code',
    'migration': '2026_02_10_093000_rename_status_code_in_ai_logs_table.php',
    'guard': 'ci-guard: status_code yazimi FORBIDDEN',
    'note': 'Rename completed — status_code YASAK'
}

with open('.sab/authority.json', 'w') as f:
    json.dump(data, f, indent=2, ensure_ascii=False)

print('OK: authority.json guncellendi')
print('  - migration_canonicalization (10 tablo, P2-F)')
print('  - field_contracts.leads.crm_durumu (P0-A)')
print('  - field_contracts.ai_logs.aktiflik_kodu (P0-B)')
