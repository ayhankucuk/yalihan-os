#!/usr/bin/env python3
import os
import re
from collections import defaultdict
import json

def audit_module(module_name, paths):
    """Audit a specific module"""
    print(f"\n📊 {module_name} MODUL AUDIT")
    print("=" * 70)
    
    query_patterns = defaultdict(int)
    model_usage = defaultdict(int)
    file_stats = {}
    
    for base_path in paths:
        if not os.path.exists(base_path):
            print(f"  ⚠️  Path not found: {base_path}")
            continue
            
        # Find all PHP files
        for root, dirs, files in os.walk(base_path):
            for file in files:
                if file.endswith('.php'):
                    fpath = os.path.join(root, file)
                    try:
                        with open(fpath, 'r', errors='ignore') as f:
                            content = f.read()
                            lines = len(content.split('\n'))
                            
                            # Store file stats
                            rel_path = fpath.replace(os.getcwd() + '/', '')
                            file_stats[rel_path] = lines
                            
                            # Query patterns
                            if '::all()' in content:
                                query_patterns['all()_calls'] += 1
                            if '::get()' in content and 'with(' not in content.split('::get()')[0][-50:]:
                                query_patterns['unoptimized_get()'] += 1
                            if 'select(' in content:
                                query_patterns['select()_optimization'] += 1
                            if 'with(' in content:
                                query_patterns['eager_loading'] += 1
                            
                            # Model usage
                            models = ['Ilan', 'Talep', 'Kisi', 'User', 'Ozellik', 'Konum']
                            for model in models:
                                if model in content:
                                    model_usage[model] += 1
                    except Exception as e:
                        pass
    
    print(f"\n✅ Files analyzed: {len(file_stats)}")
    
    if query_patterns:
        print("\n🔍 Query Pattern Analysis:")
        for pattern, count in sorted(query_patterns.items()):
            print(f"  - {pattern}: {count}")
    
    if model_usage:
        print("\n🗂️ Model Usage:")
        for model, count in sorted(model_usage.items(), key=lambda x: x[1], reverse=True):
            print(f"  - {model}: {count} files")
    
    total_lines = sum(file_stats.values())
    print(f"\n📈 Code Statistics:")
    print(f"  - Total lines: {total_lines}")
    print(f"  - Average per file: {total_lines // len(file_stats) if file_stats else 0}")
    
    return {
        'name': module_name,
        'files': len(file_stats),
        'total_lines': total_lines,
        'queries': dict(query_patterns),
        'models': dict(model_usage)
    }

# Run audits
results = []

print("\n" + "=" * 70)
print("🚀 MODÜLER SISTEM AUDIT")
print("=" * 70)

# Admin
results.append(audit_module('ADMIN', [
    'app/Http/Controllers/Admin',
    'app/Modules/Admin'
]))

# Emlak
results.append(audit_module('EMLAK', [
    'app/Http/Controllers/Api/V1/Emlak',
    'app/Modules/Emlak'
]))

# Talep
results.append(audit_module('TALEP', [
    'app/Http/Controllers/Api/V1/Talep',
    'app/Modules/Talep'
]))

# Auth
results.append(audit_module('AUTH', [
    'app/Modules/Auth',
    'app/Http/Controllers/Auth'
]))

# Cortex
results.append(audit_module('CORTEX', [
    'app/Modules/Cortex',
    'app/Http/Controllers/Api/V1/Cortex'
]))

# Summary
print("\n" + "=" * 70)
print("📊 AUDIT ÖZETI")
print("=" * 70)

total_files = sum(r['files'] for r in results)
total_lines = sum(r['total_lines'] for r in results)

for result in results:
    print(f"\n{result['name']:15} | Files: {result['files']:3} | Lines: {result['total_lines']:5}")

print(f"\n{'TOPLAM':15} | Files: {total_files:3} | Lines: {total_lines:5}")

print("\n" + "=" * 70)
print("✅ Modüler Audit Tamamlandı")
