# Refactoring Architecture - Tester Adapter

## Vue d'ensemble

L'adapter Tester a été refactorisé pour utiliser une **architecture PSR-4 moderne** sans variables d'environnement ni scripts procéduraux.

## Changements principaux

### ✅ Nouvelles classes PSR-4

#### `JobSetup` (src/Resources/JobSetup.php)
Configure le runner Nette Tester pour activer la collecte de couverture :
- Configure `auto_prepend_file` pour injecter le collecteur de couverture
- Active PCOV/Xdebug selon disponibilité
- Hiérarchie : **pcov > phpdbg > xdebug**

```php
JobSetup::configure($runner, $prependFile, $pcovDir);
```

#### `CoverageRuntime` (src/Resources/CoverageRuntime.php)
Collecte la couverture de code dans chaque job Tester :
- Utilise `phpunit/php-code-coverage` pour la collecte
- Sérialise les fragments dans `.phpser`
- Auto-détecte le driver optimal (pcov > phpdbg > xdebug)

```php
CoverageRuntime::start($fragmentDir, $srcDirs);
```

#### `MergePostProcessor` (src/Resources/MergePostProcessor.php)
Fusionne les fragments de couverture et normalise le JUnit XML :
- Désérialise et merge les fragments `.phpser`
- Génère le rapport XML Clover via `PhpUnitXmlFacade`
- Normalise les attributs JUnit pour compatibilité Infection

```php
MergePostProcessor::run($fragmentDir, $outDir, $junitPath);
```

#### `Preprocessor` (src/Resources/Preprocessor.php)
Génère les scripts temporaires avec configuration embarquée :
- Crée `tester_job_setup.php` et `tester_job_prepend.php`
- Embed la config directement (pas de variables d'environnement)
- Retourne les chemins + autoload détecté

```php
$scripts = Preprocessor::prepareJobScripts(
    $projectDir,
    $tmpDir,
    $srcDirs,
    $fragmentDir,
    $pcovDir
);
// $scripts = ['setup' => '...', 'prepend' => '...', 'autoload' => '...', 'written' => [...]]
```

### ❌ Suppressions

#### Scripts procéduraux supprimés
Tous les scripts `.infection.php` de `resources/` ont été supprimés :
- `tester_job_prepend.infection.php` → `CoverageRuntime`
- `tester_job_setup.infection.php` → `JobSetup`
- `tester_merge_postprocess.infection.php` → `MergePostProcessor::run()`
- `make_protected_public.infection.php` → Supprimé (redondant avec Infection)
- `tester_coverage_postprocess.infection.php` → Obsolète
- `tester_job_merge.infection.php` → Obsolète
- `run_infection_full.infection.php` → Obsolète
- `preprocess.infection.php` → Obsolète
- `tester_code_coverage_runner.php` → Obsolète
- `MergePostProcessor.php` (ancien) → Migré vers src/Resources/

#### Classes supprimées
- `Orchestrator` : Gérait les transformations AST (devenues inutiles)

#### Variables d'environnement supprimées
Plus besoin de :
- `INFECTION_TESTER_COVERAGE_FRAGMENT_DIR`
- `INFECTION_TESTER_COVERAGE_PREPEND`
- `INFECTION_TESTER_COVERAGE_SRC_DIRS`
- `INFECTION_TESTER_PCOV_DIR`
- `INFECTION_TESTER_VISIBILITY`
- `INFECTION_TESTER_VISIBILITY_TRANSFORM`

### 🔧 Pourquoi les transformations AST ont été supprimées

**Question** : Les transformations protected/private → public ne sont-elles pas nécessaires ?

**Réponse** : **Non, elles sont redondantes !**

Infection possède déjà son propre mécanisme :
- ✅ Utilise `IncludeInterceptor` (stream wrapper)
- ✅ Gère la visibilité au niveau du core
- ✅ Pas besoin de modifier le code source physiquement

Les transformations AST posaient des problèmes :
- ❌ Modifiaient le code sur le disque
- ❌ Cassaient le formatage
- ❌ Ajoutaient complexité + dépendance nikic/php-parser
- ❌ Dupliquaient la logique d'Infection

**Résultat** : Code plus simple, plus fiable, sans effets de bord.

## Workflow simplifié

### Initial test run

```
TesterAdapter::getInitialTestRunCommandLine()
  ↓
1. Preprocessor::prepareJobScripts()
   → Génère setup.php + prepend.php avec config embarquée
  ↓
2. Wrapper bash exécute:
   - vendor/bin/tester --setup setup.php
   - (Jobs auto-prependent prepend.php via ini)
   - CoverageRuntime collecte + dump fragments
  ↓
3. MergePostProcessor::run()
   → Merge fragments + normalise JUnit
```

### Mutant test run

```
TesterAdapter::getMutantCommandLine()
  ↓
Wrapper bash:
  - Backup fichier original
  - Copy mutant → original
  - Execute vendor/bin/tester
  - Restore original
```

## Dépendances

### Production
- `sebastianbergmann/php-code-coverage: ^11.0` : Collecte de couverture
- `nette/tester: ^2.6` : Framework de tests
- `ext-dom` : Normalisation JUnit XML

### Développement
- `nikic/php-parser: ^5.0` : **Optionnel** (plus nécessaire pour transformations)
- `phpunit/phpunit: ^11.0` : Tests
- `infection/infection: ^0.32` : Tests e2e

## Tests

```bash
cd tests/e2e/Tester
composer install
vendor/bin/infection --test-framework=tester
```

## Avantages du refactoring

1. **Simplicité** : API claire, pas de scripts externes
2. **Maintenabilité** : Code PSR-4, tests unitaires possibles
3. **Performance** : Moins d'I/O (pas de transformations AST)
4. **Sécurité** : Pas de modification du code source
5. **Compatibilité** : S'appuie sur les mécanismes natifs d'Infection

## Migration

Si vous utilisiez les anciens scripts :
- ❌ `php resources/make_protected_public.infection.php` → Plus nécessaire
- ❌ Variables `INFECTION_TESTER_*` → Plus nécessaires
- ✅ Tout fonctionne automatiquement via l'adapter

---

**Auteur** : Refactoring réalisé avec l'objectif de simplifier et moderniser l'architecture.
**Date** : 2026-02-12

