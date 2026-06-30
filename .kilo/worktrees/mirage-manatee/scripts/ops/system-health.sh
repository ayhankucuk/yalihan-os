#!/bin/bash

# EmlakPro Page Analyzer - Automated Testing Script
# Bu script sistemin sağlık durumunu kontrol eder ve rapor oluşturur

echo "🔍 EmlakPro Page Analyzer - Automated Health Check"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    case $2 in
        "success") echo -e "${GREEN}✅ $1${NC}" ;;
        "error") echo -e "${RED}❌ $1${NC}" ;;
        "warning") echo -e "${YELLOW}⚠️  $1${NC}" ;;
        "info") echo -e "${BLUE}ℹ️  $1${NC}" ;;
        *) echo "$1" ;;
    esac
}

# Check if Laravel environment is ready
check_laravel_env() {
    print_status "Checking Laravel environment..." "info"

    if [ ! -f ".env" ]; then
        print_status ".env file not found!" "error"
        return 1
    fi

    if php artisan --version > /dev/null 2>&1; then
        print_status "Laravel is ready" "success"
        return 0
    else
        print_status "Laravel environment issue" "error"
        return 1
    fi
}

# Run page analysis
run_page_analysis() {
    print_status "Running page analysis..." "info"

    # Analyze all pages
    php artisan analyze:pages --format=json --output=analysis-report.json

    if [ $? -eq 0 ]; then
        print_status "Page analysis completed" "success"

        # Check if critical issues exist
        if grep -q '"score":[0-3]' analysis-report.json; then
            print_status "Critical issues detected in pages!" "error"
            return 1
        else
            print_status "No critical issues found" "success"
            return 0
        fi
    else
        print_status "Page analysis failed" "error"
        return 1
    fi
}

# Check controller implementation status
check_controllers() {
    print_status "Checking controller implementations..." "info"

    local controllers=(
        "MyListingsController"
        "AnalyticsController"
        "NotificationController"
        "AdresYonetimiController"
        "TelegramBotController"
    )

    local issues=0

    for controller in "${controllers[@]}"; do
        local file="app/Http/Controllers/Admin/${controller}.php"

        if [ -f "$file" ]; then
            if grep -q "to be implemented" "$file"; then
                print_status "${controller}: Not implemented" "error"
                ((issues++))
            else
                print_status "${controller}: Implemented" "success"
            fi
        else
            print_status "${controller}: File not found" "error"
            ((issues++))
        fi
    done

    if [ $issues -eq 0 ]; then
        print_status "All controllers are properly implemented" "success"
        return 0
    else
        print_status "${issues} controller(s) have issues" "warning"
        return 1
    fi
}

# Check database connectivity
check_database() {
    print_status "Checking database connectivity..." "info"

    if php artisan migrate:status > /dev/null 2>&1; then
        print_status "Database connection successful" "success"
        return 0
    else
        print_status "Database connection failed" "error"
        return 1
    fi
}

# Check file permissions
check_permissions() {
    print_status "Checking file permissions..." "info"

    local dirs=("storage" "bootstrap/cache")
    local permission_issues=0

    for dir in "${dirs[@]}"; do
        if [ -w "$dir" ]; then
            print_status "${dir}: Writable" "success"
        else
            print_status "${dir}: Not writable" "error"
            ((permission_issues++))
        fi
    done

    if [ $permission_issues -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

# Generate health report
generate_health_report() {
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local report_file="health-report-$(date '+%Y%m%d-%H%M%S').txt"

    print_status "Generating health report..." "info"

    cat > "$report_file" << EOL
EmlakPro Health Check Report
============================
Generated: $timestamp

Laravel Environment: $laravel_status
Database Connection: $database_status
Controller Status: $controller_status
File Permissions: $permission_status
Page Analysis: $analysis_status

Overall Status: $overall_status

Recommendations:
- Implement missing controllers (Priority: Critical)
- Fix database schema issues in AdresYonetimiController
- Add proper error handling and validation
- Ensure Context7 compliance in all files

For detailed analysis, run:
php artisan analyze:pages --format=console

For real-time monitoring, visit:
/admin/page-analyzer
EOL

    print_status "Health report saved to: $report_file" "success"
}

# Main execution
main() {
    local exit_code=0

    # Run all checks
    check_laravel_env
    laravel_status=$?

    check_database
    database_status=$?

    check_controllers
    controller_status=$?

    check_permissions
    permission_status=$?

    run_page_analysis
    analysis_status=$?

    # Determine overall status
    if [ $laravel_status -eq 0 ] && [ $database_status -eq 0 ] && [ $controller_status -eq 0 ] && [ $permission_status -eq 0 ] && [ $analysis_status -eq 0 ]; then
        overall_status="HEALTHY"
        print_status "Overall system status: HEALTHY" "success"
    elif [ $laravel_status -ne 0 ] || [ $database_status -ne 0 ]; then
        overall_status="CRITICAL"
        print_status "Overall system status: CRITICAL" "error"
        exit_code=1
    else
        overall_status="WARNING"
        print_status "Overall system status: WARNING" "warning"
    fi

    echo ""
    generate_health_report

    echo ""
    print_status "Health check completed. Exit code: $exit_code" "info"

    exit $exit_code
}

# Run the main function
main "$@"
