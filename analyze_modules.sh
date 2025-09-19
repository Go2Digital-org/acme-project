#!/bin/bash

echo "=== PHPStan Module-by-Module Analysis ==="
echo "Date: $(date)"
echo "PHPStan Level: 8"
echo ""

modules=("Admin" "Analytics" "Audit" "Auth" "Bookmark" "CacheWarming" "Campaign" "Category" "Compliance" "Currency" "Dashboard" "DevTools" "Donation" "Export" "Import" "Localization" "Notification" "Organization" "Search" "Shared" "Team" "Tenancy" "Theme" "User")

total_modules=0
clean_modules=0

for module in "${modules[@]}"; do
    echo "Analyzing module: $module"

    # Run PHPStan on individual module
    result=$(./vendor/bin/phpstan analyse "modules/$module" --no-progress --no-interaction --error-format=json 2>/dev/null)

    # Extract error counts
    errors=$(echo "$result" | grep -o '"errors":[0-9]*' | cut -d: -f2)
    file_errors=$(echo "$result" | grep -o '"file_errors":[0-9]*' | cut -d: -f2)

    total_modules=$((total_modules + 1))

    if [ "$errors" = "0" ] && [ "$file_errors" = "0" ]; then
        clean_modules=$((clean_modules + 1))
        echo "  ✅ $module: 0 errors, 0 file errors"
    else
        echo "  ❌ $module: $errors errors, $file_errors file errors"
    fi

    echo ""
done

echo "=== SUMMARY ==="
echo "Total modules analyzed: $total_modules"
echo "Modules passing PHPStan level 8: $clean_modules"
echo "Success rate: $(( (clean_modules * 100) / total_modules ))%"

if [ $clean_modules -eq $total_modules ]; then
    echo "🎉 ALL MODULES PASS PHPStan LEVEL 8 - 100% COMPLIANCE ACHIEVED!"
else
    echo "⚠️  Some modules still have PHPStan errors"
fi