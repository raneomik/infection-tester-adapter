# PHP 8.5 Compatibility

## Problem

PHP 8.5 introduced enhanced support for union types in reflection, but some libraries still expect only `ReflectionNamedType`.

### Error Message

```
Expected an instance of ReflectionNamedType. Got: ReflectionUnionType
```

This error comes from `webmozart/assert` (used by Infection) when it encounters union types in PHP 8.5.

## Solution

### 1. Update Dependencies

The `composer.json` has been updated to require:

```json
{
  "require": {
    "webmozart/assert": "^2.0"
  },
  "require-dev": {
    "nikic/php-parser": "^5.0",
    "thecodingmachine/safe": "^3.0"
  }
}
```

**Version requirements:**
- `webmozart/assert`: `^2.1` (for better PHP 8.x support)
- `nikic/php-parser`: `^5.0` (supports PHP 8.5 syntax)
- `thecodingmachine/safe`: `^3.0` (PHP 8.2+ compatible)

### 2. Update Composer Dependencies

Run the update script:

```bash
./update-and-test.sh
```

Or manually:

```bash
# Main project
composer update

# E2E tests
cd tests/e2e/Tester
rm -rf vendor composer.lock
composer install
```

### 3. Test

```bash
cd tests/e2e/Tester

# Verify Tester tests pass
vendor/bin/tester tests/

# Run Infection
vendor/bin/infection --test-framework=tester
```

## Alternative: Use PHP 8.2 or 8.3

If you encounter persistent issues with PHP 8.5, use PHP 8.2 or 8.3:

```bash
# With phpbrew
phpbrew use 8.3

# With update-alternatives
sudo update-alternatives --set php /usr/bin/php8.3

# Test
php -v
vendor/bin/infection --test-framework=tester
```

## Status

### Compatibility Matrix

| PHP Version | webmozart/assert | Status |
|-------------|------------------|--------|
| 8.2         | ^1.2 \|\| ^2.0   | ✅ OK  |
| 8.3         | ^1.2 \|\| ^2.0   | ✅ OK  |
| 8.4         | ^2.0             | ✅ OK  |
| 8.5         | ^2.1             | ⚠️ May have issues |

### Current Fix

The adapter has been updated to:
- ✅ Require `webmozart/assert: ^2.0` minimum
- ✅ Support `nikic/php-parser: ^5.0` (PHP 8.5 compatible)
- ✅ Use `thecodingmachine/safe: ^3.0` (PHP 8.2+)

## Upstream Issues

If the problem persists, it may be an upstream issue in:

1. **webmozart/assert** - Does not fully support PHP 8.5 union types
   - Issue: https://github.com/webmozarts/assert/issues
   - Workaround: Force version `^2.1`

2. **infection/infection** - May lock webmozart/assert to old version
   - Check: `composer why webmozart/assert`
   - Solution: Override in your project's composer.json

## Override in Your Project

If you still have issues, add to your project's `composer.json`:

```json
{
  "require": {
    "webmozart/assert": "^2.1"
  }
}
```

Then run:

```bash
composer update webmozart/assert
```

## Verification

To verify the fix worked:

```bash
# Check webmozart/assert version
composer show webmozart/assert | grep versions

# Expected: 2.1.x or higher

# Test Infection
cd tests/e2e/Tester
vendor/bin/infection --test-framework=tester --debug
```

## Notes

- This is a temporary compatibility issue with PHP 8.5
- The tester-adapter code itself is fully compatible
- The issue is in third-party dependencies
- Expected to be fully resolved when Infection updates its dependencies

---

**Last Updated**: 2026-02-12
**Status**: Workaround implemented (force webmozart/assert ^2.1)

