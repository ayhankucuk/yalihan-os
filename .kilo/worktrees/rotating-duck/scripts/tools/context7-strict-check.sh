#!/usr/bin/env bash

# 🛡️ Yalıhan Bekçi: Context7 Strict Integrity Scan
# This script is for manual governance checks and is NOT included in the CI quality gate.

php artisan standard:check --type=context7
