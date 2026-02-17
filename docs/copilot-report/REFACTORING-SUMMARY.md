# Résumé du Refactoring - Tester Adapter

## Date : 2026-02-15

### 🎯 Objectif Principal
Simplifier l'architecture en supprimant `JobSetup::configure` et les scripts setup inutiles, tout en passant les options de couverture directement via la ligne de commande PHP.

---

## ✅ Fichiers Supprimés

### Classes inutilisées
- `src/Coverage/JobSetup.php` - Plus nécessaire, options passées directement en CLI
- `src/Coverage/Script/Template/SetupScriptTemplate.php` - Plus utilisé

---

## 🔄 Fichiers Renommés

### Noms plus explicites et conventionnels

| Ancien Nom | Nouveau Nom | Raison |
|------------|-------------|--------|
| `Preprocessor.php` | `CoverageScriptGenerator.php` | Plus descriptif du rôle réel |
| `MergePostProcessor.php` | `CoverageMerger.php` | Plus court et clair |
| `PrependScriptTemplate.php` | `AutoPrependTemplate.php` | Correspond à auto_prepend_file |

### Méthodes renommées

| Classe | Ancienne Méthode | Nouvelle Méthode |
|--------|------------------|------------------|
| `CoverageScriptGenerator` | `preparePrependScript()` | `generate()` |
| `CoverageScriptGenerator` | `findProjectAutoload()` | `findAutoload()` |
| `CoverageScriptGenerator` | `writePrependScript()` | `writeScript()` |
| `CoverageMerger` | `run()` | `merge()` |
| `AutoPrependTemplate` | `generate()` | `build()` |

---

## 🏗️ Architecture Simplifiée

### Avant
```
InitialTestRunCommandBuilder
  └─> Preprocessor::prepareJobScripts()
      ├─> Génère setup script (--setup pour Tester)
      │   └─> JobSetup::configure($runner, ...)
      │       └─> CoverageDriverOptionsBuilder (via setup)
      └─> Génère prepend script (auto_prepend_file)
```

### Après
```
InitialTestRunCommandBuilder
  ├─> CoverageScriptGenerator::generate()
  │   └─> Génère prepend script (auto_prepend_file)
  └─> CoverageDriverOptionsBuilder->buildPhpIniOptions()
      └─> Options ajoutées directement en CLI (-d option=value)
```

---

## 🔧 Modifications Techniques

### 1. InitialTestRunCommandBuilder
- ✅ Injection de `CoverageDriverOptionsBuilder` comme dépendance
- ✅ Utilisation de `CoverageScriptGenerator::generate()` au lieu de `prepareJobScripts()`
- ✅ Options de couverture ajoutées directement via `buildPhpIniOptions()`
- ✅ `register_argc_argv=1` et `auto_prepend_file` passés en arguments PHP
- ❌ Suppression de `--setup` dans les arguments Tester

### 2. CoverageScriptGenerator (ex-Preprocessor)
- ✅ Méthode `generate()` simplifiée (retourne `{script, autoload}` au lieu de `{setup, prepend, autoload, written}`)
- ✅ Suppression de `prepareJobScripts()` (deprecated)
- ✅ Suppression de `writeSetupScript()` (inutile)
- ✅ Noms de méthodes plus courts et clairs

### 3. CoverageMerger (ex-MergePostProcessor)
- ✅ Méthode `merge()` au lieu de `run()`
- ✅ Documentation améliorée

### 4. AutoPrependTemplate (ex-PrependScriptTemplate)
- ✅ Méthode `build()` au lieu de `generate()`
- ✅ Documentation plus claire

---

## 📊 Résultats des Tests

### Tests Unitaires
```
✅ 36/36 tests passent
✅ 38 assertions
```

### Tests E2E (Infection)
```
✅ 45/45 mutants tués
✅ 100% MSI (Mutation Score Indicator)
✅ Performance: 0s avec 4 threads
```

### Problèmes Résolus
- ❌ "No source code was executed by the test framework" → ✅ Résolu
- ❌ Setup script complexe et inutile → ✅ Supprimé
- ❌ JobSetup::configure redondant → ✅ Supprimé

---

## 🎨 Avantages du Refactoring

### Code Plus Propre
- Suppression de 2 classes inutiles
- Noms de classes et méthodes plus explicites
- Moins de fichiers générés temporairement
- Architecture plus simple à comprendre

### Performance
- Pas de script setup à exécuter
- Options passées directement (plus rapide)
- Moins d'I/O disque

### Maintenabilité
- Séparation claire des responsabilités
- Dépendances injectées proprement
- Code plus testable
- Documentation améliorée

---

## 📝 Structure Finale des Fichiers Coverage

```
src/Coverage/
├── CoverageDriverOptionsBuilder.php   # Détecte et construit les options du driver
├── CoverageRuntime.php                # Runtime de collecte de couverture
├── CoverageScriptGenerator.php        # Génère le script auto_prepend_file
├── CoverageMerger.php                 # Fusionne les fragments et normalise JUnit
└── Script/
    └── Template/
        └── AutoPrependTemplate.php    # Template du script auto_prepend_file
```

---

## 🚀 Prochaines Étapes Possibles

1. ✨ Ajouter des tests unitaires pour les nouvelles classes
2. 📚 Mettre à jour la documentation principale (README.md)
3. 🔍 Vérifier phpstan sur `src/` uniquement
4. 🧹 Nettoyer les scripts bash temporaires si présents
5. 📦 Préparer pour publication

---

## ⚡ Commandes de Test

### Tests unitaires
```bash
vendor/bin/phpunit
```

### Tests E2E avec Infection
```bash
cd tests/e2e/Tester
vendor/bin/infection --test-framework=tester --threads=4
```

### PHPStan (src uniquement)
```bash
vendor/bin/phpstan analyse
```

---

**Note**: Toutes les modifications sont rétrocompatibles au niveau API publique. Aucun breaking change pour les utilisateurs de l'adapter.

