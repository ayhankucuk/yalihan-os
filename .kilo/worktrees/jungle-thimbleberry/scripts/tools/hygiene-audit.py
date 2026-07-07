import json
import os
import re

ROUTE_FILE = 'storage/logs/route_list.json'
BASE_DIR = os.getcwd()

def get_file_path(class_name):
    # App\Http\Controllers\Admin\DashboardController -> app/Http/Controllers/Admin/DashboardController.php
    # Consider App\Modules as well
    if class_name.startswith('App\\'):
        return os.path.join(BASE_DIR, 'app', class_name[4:].replace('\\', '/') + '.php')
    return None

def main():
    if not os.path.exists(ROUTE_FILE):
        print(f"Error: {ROUTE_FILE} not found.")
        return

    with open(ROUTE_FILE, 'r') as f:
        routes = json.load(f)

    # reports
    route_integrity = []
    policy_coverage = []
    audit_report = []

    for route in routes:
        action = route.get('action', '')
        if not action or '@' not in action or not action.startswith('App\\'):
            continue

        class_name, method_name = action.split('@')
        file_path = get_file_path(class_name)

        uri = route.get('uri', '')
        http_method = route.get('method', '')
        middleware = route.get('middleware', [])

        if not file_path or not os.path.exists(file_path):
            route_integrity.append(f"- [ ] **[PHANTOM_ROUTE]** {http_method} {uri} -> {action} (Class not found)")
            continue

        with open(file_path, 'r', encoding='utf-8') as cf:
            content = cf.read()

        # extract method body
        safe_method_name = re.escape(method_name)
        method_pattern = re.compile(
            rf'function\s+{safe_method_name}\s*\([^)]*\)\s*(?::\s*[^{{]+)?\s*{{',
            re.DOTALL,
        )
        match = method_pattern.search(content)

        if not match:
            # check if it's from a trait or extend (we just flag it, might need manual check)
            route_integrity.append(f"- [ ] **[METHOD_NOT_FOUND]** {http_method} {uri} -> {action} (Method missing in {os.path.basename(file_path)})")
            continue

        start_idx = match.end() - 1
        open_braces = 0
        end_idx = start_idx
        for i in range(start_idx, len(content)):
            if content[i] == '{':
                open_braces += 1
            elif content[i] == '}':
                open_braces -= 1
                if open_braces == 0:
                    end_idx = i
                    break

        method_body = content[start_idx:end_idx+1]

        is_write = any(m in http_method for m in ['POST', 'PUT', 'PATCH', 'DELETE'])

        is_admin_surface = (
            uri.startswith('admin/')
            or uri.startswith('api/v1/admin/')
            or any('admin' in str(m).lower() for m in middleware)
        )

        if is_write:
            # Check Policy
            def _has_policy_marker(m):
                if not m:
                    return False
                marker = str(m).lower()
                return (
                    'can:' in marker
                    or 'role:' in marker
                    or 'permission:' in marker
                    or 'rolemiddleware:' in marker
                    or 'permissionmiddleware:' in marker
                )

            has_policy_middleware = any(_has_policy_marker(m) for m in middleware)
            has_explicit_auth = bool(re.search(r'\$?this->authorize\(|Gate::authorize\(|Gate::allows\(|\$request->user\(\)->can\(', method_body))
            has_global_write_guard = any(
                m and ('sab.write.guard' in str(m).lower() or 'globalwriteguard' in str(m).lower())
                for m in middleware
            )
            has_admin_middleware = any(m and 'admin' in str(m).lower() for m in middleware)

            # Check constructor for policies
            constructor_pattern = re.compile(r'function\s+__construct\s*\(.*?\)\s*{(.*?)}', re.DOTALL)
            constructor_match = constructor_pattern.search(content)
            has_constructor_auth = False
            if constructor_match:
                constructor_body = constructor_match.group(1)
                has_constructor_auth = bool(re.search(r'authorizeResource\(|middleware\(\s*[\'"](can|role|permission):', constructor_body))

            # Also check if Base controller has it or it is a public unauthenticated route explicitly by domain config but let's stick to simple rules
            is_auth_enforced = (
                has_policy_middleware
                or has_explicit_auth
                or has_constructor_auth
                or has_global_write_guard
                or has_admin_middleware
            )

            if is_admin_surface and not is_auth_enforced:
                policy_coverage.append(f"- [ ] **[WRITE_POLICY_MISSING]** {http_method} {uri} -> {action}")

            # Check Direct DB Write
            has_direct_write = bool(re.search(r'->save\(\)|->update\(|->delete\(\)|::create\(|::updateOrCreate\(|::firstOrCreate\(', method_body))
            if has_direct_write:
                audit_report.append(f"- [ ] **[DIRECT_DB_WRITE]** {http_method} {uri} -> {action}")

            # Check Action Dispatch (very basic)
            has_delegation = bool(re.search(r'->execute\(|->handle\(|->store\(|->update\(|->destroy\(|->dispatch\(|dispatch\(', method_body))
            has_service_or_action = bool(re.search(r'(Service|Action)(::|->)', method_body)) or bool(re.search(r'->\w+Service->', content))
            if not has_delegation and not has_service_or_action and not has_direct_write:
                 # Check if it returns directly
                 if not re.search(r'return\s+view\(|return\s+response\(', method_body):
                     audit_report.append(f"- [ ] **[WRITE_NO_ACTION_DISPATCH]** {http_method} {uri} -> {action}")

        # Check Silent Catch
        silent_catch = re.search(r'catch\s*\([^)]+\)\s*\{\s*(?:Log::[^;]+;\s*)?\}', method_body)
        if silent_catch:
            audit_report.append(f"- [ ] **[SILENT_CATCH]** {http_method} {uri} -> {action}")

    baseline = {'route_integrity': [], 'policy_coverage': [], 'audit_report': []}
    baseline_path = 'storage/app/sab/baseline.json'
    if os.path.exists(baseline_path):
        with open(baseline_path, 'r') as f:
            baseline = json.load(f)

    def is_new(issue, category):
        return issue not in baseline.get(category, [])

    new_integrity = [i for i in route_integrity if is_new(i, 'route_integrity')]
    new_policy = [i for i in policy_coverage if is_new(i, 'policy_coverage')]
    new_audit = [i for i in audit_report if is_new(i, 'audit_report')]

    os.makedirs('docs/reports', exist_ok=True)

    with open('docs/reports/hygiene_route_integrity_v11_0.md', 'w') as f:
        f.write("# Route Integrity Report\n\n")
        f.write("\n".join(route_integrity) if route_integrity else "✅ All routes are intact.")

    with open('docs/reports/hygiene_policy_coverage_v11_0.md', 'w') as f:
        f.write("# Policy Coverage Report\n\n")
        f.write("\n".join(policy_coverage) if policy_coverage else "✅ All write routes have policy coverage.")

    with open('docs/reports/hygiene_audit_v11_0.md', 'w') as f:
        f.write("# General Hygiene Audit\n\n")
        f.write("\n".join(audit_report) if audit_report else "✅ No direct writes or silent catches found.")

    with open('docs/reports/hygiene_report.json', 'w') as f:
        json.dump({
            'route_integrity': route_integrity,
            'policy_coverage': policy_coverage,
            'audit_report': audit_report
        }, f, indent=4)

    total_new = len(new_integrity) + len(new_policy) + len(new_audit)
    print(f"Audit completed. Total issues: {len(route_integrity) + len(policy_coverage) + len(audit_report)}.")
    print(f"NEW issues (Delta): {total_new} (Integrity: {len(new_integrity)}, Policy: {len(new_policy)}, Audit: {len(new_audit)})")

    if total_new > 0:
        print("\n🚨 NEW VIOLATIONS DETECTED:")
        for i in new_integrity + new_policy + new_audit:
            print(i)
        exit(1)
    else:
        print("\n✅ No new violations. Baseline is protected.")
        exit(0)

if __name__ == '__main__':
    main()
