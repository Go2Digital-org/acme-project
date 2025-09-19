# Code Quality and Linting Tools

## Overview

ACME Corp CSR Platform uses a comprehensive suite of code quality tools to maintain high standards and consistency across the codebase.

## Automated Code Quality Tools

### 1. Laravel Pint (Code Style)
Laravel's official code style fixer based on PHP-CS-Fixer.

```bash
# Check code style
./vendor/bin/pint --test

# Auto-fix code style issues
./vendor/bin/pint

# Fix specific directory
./vendor/bin/pint modules/Campaign
```

**Configuration**: `pint.json`
- PSR-12 compliance
- Laravel conventions
- Automatic formatting on commit

### 2. Rector (Automated Refactoring)
PHP code quality tool for automated refactoring and upgrades.

```bash
# Run safe refactoring (dry-run)
./vendor/bin/rector process --config=rector/rector-safe.php --dry-run

# Apply safe refactoring
./vendor/bin/rector process --config=rector/rector-safe.php

# PHPStan type fixes (dry-run) - NEW: Array type hints and PHPStan fixes
composer rector:phpstan-fix

# Apply PHPStan type fixes - NEW: Aggressive array type improvements
composer rector:phpstan-fix:apply

# Check what changes would be made
./vendor/bin/rector process --dry-run

# Apply all configured rules
./vendor/bin/rector process
```

**Configuration Files**:
- `rector.php` - Main configuration
- `rector/rector-safe.php` - Conservative safe refactoring
- `rector/rector-phpstan-fix.php` - **NEW**: PHPStan array type fixes and improvements

**Rector Profiles**:
- **Safe**: Type declarations, dead code removal, basic code quality
- **PHPStan Fix**: Array type hints, closure return types, strict typed properties
- **Aggressive**: PHP 8.4 features adoption, advanced transformations

### 3. PHPStan (Static Analysis)
Finds bugs without running code through static analysis.

```bash
# Run analysis at Level 8 (strictest)
./vendor/bin/phpstan analyse

# Analyze specific module
./vendor/bin/phpstan analyse modules/Campaign

# Clear cache and re-analyze
./vendor/bin/phpstan clear-result-cache
./vendor/bin/phpstan analyse
```

**Configuration**: `phpstan.neon`
- Level 8 (maximum strictness)
- Custom rules for hexagonal architecture
- Comprehensive type checking

### 4. Deptrac (Architecture Boundaries)
Ensures architectural rules and layer boundaries are respected.

```bash
# Check architecture boundaries
./vendor/bin/deptrac analyse

# Generate dependency report
./vendor/bin/deptrac analyse --formatter=graphviz --output=dependencies.dot
```

**Configuration**: `deptrac.yaml`
- Hexagonal architecture enforcement
- Module isolation validation
- Dependency direction rules

### 5. GrumPHP (Git Hooks Integration)
Automated quality checks on every commit.

```bash
# Run all checks
./vendor/bin/grumphp run

# Run quick checks only
./vendor/bin/grumphp run --testsuite=quick

# Run standard checks
./vendor/bin/grumphp run --testsuite=standard

# Run full validation
./vendor/bin/grumphp run --testsuite=full
```

**Configuration**: `grumphp.yml`
- Pre-commit hooks
- Parallel execution
- Auto-fixing enabled

## Pre-Commit Workflow

When you commit code, GrumPHP automatically:

1. **Checks for debug code** (dd, dump, var_dump, console.log)
2. **Runs Rector** (safe refactoring in dry-run mode)
3. **Runs Laravel Pint** (auto-fixes code style)
4. **Runs PHPStan** (static analysis)
5. **Validates architecture** with Deptrac

## Manual Commands

### Quick Check & Fix
```bash
# Auto-fix everything possible
composer run pre-commit-checks

# This runs:
# 1. Rector safe refactoring check
# 2. Laravel Pint auto-fix
```

### Full Quality Check
```bash
# Run all quality tools
./vendor/bin/grumphp run --testsuite=full
```

### Module-Specific Checks
```bash
# Check specific module
./vendor/bin/pint modules/Campaign
./vendor/bin/phpstan analyse modules/Campaign
./vendor/bin/rector process modules/Campaign --dry-run
```

## CI/CD Integration

All quality tools run automatically in GitHub Actions:

```yaml
- name: Code Quality Checks
  run: |
    vendor/bin/rector process --dry-run
    vendor/bin/pint --test
    vendor/bin/phpstan analyse
    vendor/bin/deptrac analyse
```

## Tool Configuration Files

| Tool | Configuration | Purpose |
|------|--------------|---------|
| Laravel Pint | `pint.json` | Code style rules |
| Rector | `rector.php`, `rector/rector-safe.php` | Refactoring rules |
| PHPStan | `phpstan.neon` | Static analysis rules |
| Deptrac | `deptrac.yaml` | Architecture boundaries |
| GrumPHP | `grumphp.yml` | Git hooks configuration |

## Best Practices

1. **Always run checks before pushing**
   ```bash
   ./vendor/bin/grumphp run
   ```

2. **Fix issues incrementally**
   - Start with Pint (code style)
   - Then Rector (safe refactoring)
   - Finally PHPStan (type issues)

3. **Use auto-fix when possible**
   ```bash
   # Auto-fix style and apply safe refactoring
   composer run pre-commit-checks
   ```

4. **Keep tools updated**
   ```bash
   composer update --dev
   ```

## Common Issues and Solutions

### Rector Changes Too Much
Use the safe configuration:
```bash
./vendor/bin/rector process --config=rector/rector-safe.php
```

### PHPStan Memory Issues
Increase memory limit:
```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse
```

### Pint Conflicts with IDE
Configure your IDE to use project's Pint rules:
- PHPStorm: Settings > PHP > Quality Tools > PHP CS Fixer
- VSCode: Use Laravel Pint extension

### GrumPHP Blocking Commits
For emergency commits (use sparingly):
```bash
git commit --no-verify -m "Emergency fix"
```

## Performance Tips

1. **Use parallel processing**
   - GrumPHP runs up to 8 parallel workers
   - Significantly faster on multi-core systems

2. **Leverage caching**
   - PHPStan caches analysis results
   - Deptrac caches dependency graphs
   - Clear caches if you see stale results

3. **Run targeted checks**
   - Check only changed files when possible
   - Use module-specific commands for faster feedback

---

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved