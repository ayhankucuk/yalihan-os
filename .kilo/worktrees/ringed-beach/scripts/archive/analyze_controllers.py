#!/usr/bin/env python3
import os
import re
from pathlib import Path

def analyze_controller_queries(filepath):
    """Analyze query patterns in a controller file"""
    with open(filepath, 'r', errors='ignore') as f:
        content = f.read()
    
    # Extract class name
    class_match = re.search(r'class\s+(\w+)', content)
    class_name = class_match.group(1) if class_match else "Unknown"
    
    # Count methods
    methods = re.findall(r'public\s+function\s+(\w+)\s*\(', content)
    
    # Query patterns
    patterns = {
        'all()': content.count('::all()'),
        'get()': content.count('::get()'),
        'select()': content.count('select('),
        'with()': content.count('with('),
        'paginate()': content.count('paginate('),
        'find()': content.count('::find('),
        'first()': content.count('::first('),
    }
    
    # Issues
    issues = []
    if patterns['all()'] > 0:
        issues.append(f"❌ ::all() called {patterns['all()']} times")
    if patterns['get()'] > 0 and patterns['with()'] == 0 and patterns['select()'] == 0:
        issues.append(f"⚠️ get() without eager loading or select")
    
    lines = len(content.split('\n'))
    
    return {
        'file': Path(filepath).name,
        'class': class_name,
        'lines': lines,
        'methods': len(methods),
        'patterns': patterns,
        'issues': issues,
    }

print("\n" + "="*80)
print("📊 ADMIN CONTROLLERS - DETAILED QUERY ANALYSIS")
print("="*80)

# Critical Ilan controllers
controllers = [
    "app/Http/Controllers/Admin/IlanController.php",
    "app/Http/Controllers/Admin/IlanCrudController.php",
    "app/Http/Controllers/Admin/IlanApiController.php",
    "app/Http/Controllers/Admin/AdminController.php",
    "app/Http/Controllers/Admin/IlanPublishController.php",
]

results = []
for ctrl_path in controllers:
    if Path(ctrl_path).exists():
        analysis = analyze_controller_queries(ctrl_path)
        results.append(analysis)

# Print results
for idx, result in enumerate(results, 1):
    print(f"\n{idx}. {result['file']}")
    print(f"   Class: {result['class']}")
    print(f"   Lines: {result['lines']} | Methods: {result['methods']}")
    print(f"   Queries: ", end="")
    
    q_parts = []
    if result['patterns']['select()'] > 0:
        q_parts.append(f"✅ select()×{result['patterns']['select()']}")
    if result['patterns']['with()'] > 0:
        q_parts.append(f"✅ with()×{result['patterns']['with()']}")
    if result['patterns']['all()'] > 0:
        q_parts.append(f"⚠️ all()×{result['patterns']['all()']}")
    if result['patterns']['get()'] > 0:
        q_parts.append(f"get()×{result['patterns']['get()']}")
    
    print(" | ".join(q_parts if q_parts else ["No major patterns"]))
    
    if result['issues']:
        for issue in result['issues']:
            print(f"      {issue}")

# Summary stats
print("\n" + "="*80)
print("SUMMARY STATISTICS")
print("="*80)

total_lines = sum(r['lines'] for r in results)
total_methods = sum(r['methods'] for r in results)
total_select = sum(r['patterns']['select()'] for r in results)
total_with = sum(r['patterns']['with()'] for r in results)
total_all = sum(r['patterns']['all()'] for r in results)

print(f"Total Controllers:     {len(results)}")
print(f"Total Lines:           {total_lines}")
print(f"Total Methods:         {total_methods}")
print(f"\nQuery Optimizations:")
print(f"  ✅ select() calls:    {total_select}")
print(f"  ✅ with() calls:      {total_with}")
print(f"  ⚠️ all() calls:       {total_all}")

print("\n" + "="*80)
print("✅ Controller Analysis Complete")
