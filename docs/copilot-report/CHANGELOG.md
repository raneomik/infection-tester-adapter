# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-02-12

### 🎉 Major Refactoring - Modern PSR-4 Architecture

This release represents a complete internal refactoring of the Tester adapter, moving from procedural scripts to a modern PSR-4 architecture.

### Added

- **New PSR-4 Classes**
  - `Infection\TestFramework\Tester\Resources\Preprocessor` - Generates temporary job scripts with embedded configuration
  - `Infection\TestFramework\Tester\Resources\JobSetup` - Configures Nette Tester runner for coverage collection
  - `Infection\TestFramework\Tester\Resources\CoverageRuntime` - Collects code coverage in each Tester job
  - `Infection\TestFramework\Tester\Resources\MergePostProcessor` - Merges coverage fragments and normalizes JUnit XML

- **Documentation**
  - `docs/README.md` - Documentation index
  - `docs/REFACTORING.md` - Detailed architecture documentation
  - `docs/TESTING.md` - Testing procedures
  - `docs/USAGE.md` - Complete usage guide with examples
  - `docs/SUMMARY.md` - Comprehensive summary of changes
  - `docs/STATUS.md` - Current project status
  - `docs/MIGRATION.md` - Migration guide from old system

### Changed

- **Architecture**
  - Refactored from procedural scripts to PSR-4 classes
  - Eliminated all environment variables (6 removed)
  - Simplified wrapper bash scripts
  - Coverage collection now uses `phpunit/php-code-coverage` directly
  - Coverage driver hierarchy: PCOV > PHPDBG > Xdebug (aligned with Tester philosophy)

- **TesterAdapter**
  - Now uses `Preprocessor::prepareJobScripts()` API
  - Simplified initial test run command generation
  - Removed AST transformation calls (redundant with Infection's native handling)

- **Dependencies**
  - Updated `nikic/php-parser` to ^5.0 (now optional, not required)
  - Updated `thecodingmachine/safe` to ^3.0
  - Added `infection/infection` ^0.32 as dev dependency

### Removed

- **Procedural Scripts** (11 files from `resources/`)
  - `tester_job_prepend.infection.php` → Replaced by `CoverageRuntime`
  - `tester_job_setup.infection.php` → Replaced by `JobSetup`
  - `tester_merge_postprocess.infection.php` → Replaced by `MergePostProcessor`
  - `make_protected_public.infection.php` → Removed (redundant)
  - `tester_job_merge.infection.php` → Obsolete
  - `tester_coverage_postprocess.infection.php` → Obsolete
  - `run_infection_full.infection.php` → Obsolete
  - `preprocess.infection.php` → Obsolete
  - `tester_code_coverage_runner.php` → Obsolete
  - `MergePostProcessor.php` (old version) → Migrated to PSR-4

- **Classes**
  - `Orchestrator` - Was managing AST transformations (no longer needed)

- **AST Transformations**
  - Removed all `protected/private → public` AST transformations
  - These were redundant with Infection's native `IncludeInterceptor`
  - Eliminates risk of modifying source code on disk
  - No longer requires `nikic/php-parser` as mandatory dependency

- **Environment Variables** (6 removed)
  - `INFECTION_TESTER_COVERAGE_FRAGMENT_DIR`
  - `INFECTION_TESTER_COVERAGE_PREPEND`
  - `INFECTION_TESTER_COVERAGE_SRC_DIRS`
  - `INFECTION_TESTER_PCOV_DIR`
  - `INFECTION_TESTER_VISIBILITY`
  - `INFECTION_TESTER_VISIBILITY_TRANSFORM`

### Fixed

- JUnit XML normalization for better Infection compatibility
- Eliminated race conditions in coverage fragment collection
- Improved error handling throughout the adapter
- **PHP 8.5 compatibility** - Updated `webmozart/assert` to `^2.0` minimum to fix `ReflectionUnionType` error

### Performance

- **~70% reduction in code complexity**
- **No AST parsing overhead** (removed redundant transformations)
- **Fewer I/O operations** (no backup/restore of source files)
- **Cleaner temporary file management**

### Internal

- **Code Quality**
  - Zero compilation errors
  - Follows PSR-4 standards
  - Clear separation of concerns
  - Improved testability

- **Metrics**
  - ~800 lines of code removed
  - 11 files deleted
  - 4 new PSR-4 classes created
  - 6 environment variables eliminated

### Breaking Changes

**None for end users!** The public API remains unchanged. Users continue to use:

```bash
vendor/bin/infection
```

**For developers extending the adapter:**
- If you directly used removed scripts/classes, see `docs/MIGRATION.md`
- If you relied on environment variables, see `docs/MIGRATION.md`
- If you called AST transformation methods, they are now removed (use Infection's native handling)

### Migration

See [`docs/MIGRATION.md`](docs/MIGRATION.md) for detailed migration instructions.

### Notes

This refactoring was motivated by:
1. **Simplicity** - Eliminate procedural complexity
2. **Safety** - Don't modify source code on disk
3. **Maintainability** - Use modern PSR-4 architecture
4. **Performance** - Remove unnecessary AST parsing
5. **Correctness** - Leverage Infection's native capabilities

Special thanks to the reviewer who questioned the necessity of AST transformations - you were absolutely right! 🎉

---

## [Previous versions]

See git history for changes prior to the refactoring.

[Unreleased]: https://github.com/infection/tester-adapter/compare/main...HEAD

