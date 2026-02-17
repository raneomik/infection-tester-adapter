# 🔧 Plan de Refactoring - Architecture Moderne

## ✅ Problèmes identifiés

1. **Modifications restent après Infection** - Backups `.infection.bak.hash` non nettoyés
2. **Performances lentes** - Copie physique de fichiers mutants (I/O intensif)
3. **Scripts générés = pas PSR-4** - `Preprocessor::prepareJobScripts()` génère du PHP
4. **Tout dans un package** - Mélange adapter + couverture

## 🎯 Solutions proposées

### 1. Nettoyage automatique des backups

**Problème** : Si un test mutant plante, le backup `.infection.bak.hash` reste.

**Solution** : Utiliser `trap` bash pour garantir le nettoyage :

```bash
#!/usr/bin/env bash
set -euo pipefail
trap 'cleanup' EXIT ERR

cleanup() {
    [ -f "$BACKUP" ] && mv "$BACKUP" "$ORIGINAL"
}

ORIGINAL="src/Calculator.php"
MUTANT="/tmp/mutant_abc.php"
BACKUP="$ORIGINAL.infection.bak.abc"

cp "$ORIGINAL" "$BACKUP"
cp "$MUTANT" "$ORIGINAL"
vendor/bin/tester tests/
# cleanup() sera TOUJOURS appelé
```

### 2. Performances - Utiliser IncludeInterceptor

**Problème actuel** :
```bash
# Pour chaque mutant :
cp original original.bak     # I/O
cp mutant original          # I/O
run test                    # OK
mv original.bak original    # I/O
# = 3 opérations I/O × 100 mutants = LENT
```

**Solution** : Utiliser le système natif d'Infection

```php
// Au lieu de copier physiquement :
public function getMutantCommandLine(...): array
{
    // Infection a déjà créé le mutant dans $mutatedFilePath
    // On utilise IncludeInterceptor pour le "swap" en mémoire
    return $this->commandLineBuilder->build(
        $this->testFrameworkExecutable,
        ['-d', 'auto_prepend_file=' . $interceptorScript],
        $testerArgs
    );
}
```

Le script interceptor :
```php
<?php
// interceptor.php - généré une seule fois
IncludeInterceptor::intercept('/path/original.php', '/path/mutant.php');
IncludeInterceptor::enable();
```

**Gain** : Plus de copie de fichiers = **10-50x plus rapide**

### 3. Remplacer scripts générés par classes PSR-4

**Actuellement** :
```php
// Preprocessor::prepareJobScripts() génère :
file_put_contents('tester_job_setup.php', $phpCode);
```

**Refactoring** :

```
src/
  Coverage/
    TesterSetup.php        # Classe pour --setup
    TesterPrepend.php      # Classe pour auto_prepend
    FragmentCollector.php  # Collecte fragments
    FragmentMerger.php     # Merge fragments
```

Utilisation :

```php
// Au lieu de générer un script, on appelle directement :
$setupScript = __DIR__ . '/Coverage/TesterSetup.php';
// Ce script contient juste :
<?php
require __DIR__ . '/../../vendor/autoload.php';
\Infection\TesterAdapter\Coverage\TesterSetup::configure($runner);
```

**Avantages** :
- ✅ Vraies classes PSR-4
- ✅ Testables unitairement
- ✅ Autocomplete IDE
- ✅ Maintenable

### 4. Monorepo avec packages séparés

**Structure proposée** :

```
libs/infection/
  ├── tester-adapter/              # Package principal
  │   ├── src/
  │   │   ├── TesterAdapter.php
  │   │   ├── TesterAdapterFactory.php
  │   │   ├── CommandLineBuilder.php
  │   │   └── ...
  │   ├── composer.json
  │   └── README.md
  │
  └── tester-coverage/             # Extension couverture (NOUVEAU)
      ├── src/
      │   ├── Setup/
      │   │   ├── TesterSetup.php
      │   │   └── SetupInterface.php
      │   ├── Collection/
      │   │   ├── FragmentCollector.php
      │   │   ├── CoverageDriver.php
      │   │   └── DriverFactory.php
      │   ├── Merge/
      │   │   ├── FragmentMerger.php
      │   │   └── JUnitNormalizer.php
      │   └── CoverageExtension.php
      ├── composer.json
      └── README.md
```

**composer.json** (tester-adapter) :

```json
{
  "name": "infection/tester-adapter",
  "require": {
    "infection/tester-coverage": "^1.0"
  }
}
```

**composer.json** (tester-coverage) :

```json
{
  "name": "infection/tester-coverage",
  "description": "Code coverage collection for Nette Tester",
  "require": {
    "nette/tester": "^2.6",
    "phpunit/php-code-coverage": "^11.0"
  }
}
```

**Avantages** :
- ✅ Séparation des responsabilités
- ✅ Réutilisable par d'autres projets
- ✅ Tests indépendants
- ✅ Versioning séparé

## 📋 Plan d'implémentation

### Phase 1 : Nettoyage automatique (30 min)
- [x] Ajouter `trap` dans wrapper mutant
- [x] Tester que backups sont nettoyés

### Phase 2 : Utiliser IncludeInterceptor (2h)
- [ ] Modifier `TesterAdapter::getMutantCommandLine()`
- [ ] Supprimer copie physique de fichiers
- [ ] Utiliser `IncludeInterceptor` natif d'Infection
- [ ] Benchmark performances

### Phase 3 : Classes PSR-4 au lieu de scripts (3h)
- [ ] Créer `src/Coverage/TesterSetup.php`
- [ ] Créer `src/Coverage/TesterPrepend.php`
- [ ] Créer `src/Coverage/FragmentCollector.php`
- [ ] Supprimer génération dynamique dans `Preprocessor`
- [ ] Mettre à jour `TesterAdapter` pour utiliser les nouvelles classes

### Phase 4 : Monorepo (4h)
- [ ] Créer structure `tester-coverage/`
- [ ] Migrer code coverage vers package séparé
- [ ] Mettre à jour dépendances
- [ ] Tests pour chaque package
- [ ] Documentation

## 🎯 Priorité

1. **URGENT** : Nettoyage automatique (bug)
2. **IMPORTANT** : Performances (IncludeInterceptor)
3. **AMÉLIORATION** : Classes PSR-4
4. **LONG TERME** : Monorepo

## ⏱️ Estimation

- Phase 1-2 : **1 jour** (nettoyage + perfs)
- Phase 3 : **1 jour** (refacto PSR-4)
- Phase 4 : **2 jours** (monorepo complet)

**Total** : ~4 jours de dev

## 🤔 Décision requise

Veux-tu que je :
- **A)** Juste fix le nettoyage + perfs (Phase 1-2) ?
- **B)** Refacto complet PSR-4 (Phase 1-3) ?
- **C)** Tout faire avec monorepo (Phase 1-4) ?

---

**Recommandation** : Commencer par **A** (fix rapide), puis **B** (clean code), puis **C** (long terme).

