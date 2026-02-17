# Résumé des modifications - Nettoyage des templates

## 🎯 Objectif
Nettoyer les templates pour supprimer les `require_once` inutiles et simplifier le code.

## ✅ Modifications principales

### 1. MutationBootstrapTemplate - Simplifié
**Supprimé :**
- ❌ Paramètre `$interceptorPath` (inutile - déjà dans autoload)
- ❌ Paramètre `$infectionPharLoader` (inutile)
- ❌ `require_once` de `IncludeInterceptor`

**Résultat :** Template plus simple avec seulement `require_once autoload.php`

### 2. MutationConfigBuilder - Nettoyé
- ✅ Suppression de `getInterceptorPath()` et `getInfectionPharLoader()`
- ✅ Suppression des imports inutiles
- ✅ `$originalBootstrap` maintenant **nullable** (`?string`)

### 3. Bootstrap optionnel
Le bootstrap (`tests/bootstrap.php`) est maintenant **optionnel** - plus d'exception si absent.

### 4. Templates Coverage - Conservés (justifiés)
Ces templates **gardent** leur `require_once autoload` car nécessaire :
- `PrependScriptTemplate` : exécuté via `auto_prepend_file` AVANT bootstrap
- `SetupScriptTemplate` : exécuté par Tester AVANT bootstrap
- `InitialTestRunCommand` : script wrapper autonome

### 5. Dépendances ajoutées
Dans `composer.json` principal :
```json
"require": {
    "sebastian/diff": "^6.0 || ^7.0 || ^8.0",
    "symfony/yaml": "^6.4 || ^7.4"
}
```

### 6. Namespaces mis à jour
✅ Tout utilise maintenant `Raneomik\InfectionTestFramework\Tester`

## 📋 Tests

```bash
cd tests/e2e/Tester

# Installation
rm -rf vendor composer.lock
composer install --prefer-stable

# Vérification
php check-stable.php

# Test Infection
vendor/bin/infection --test-framework=tester --dry-run
```

## ⚠️ Important : Version stable d'Infection

Le projet utilise **Infection 0.32.x stable** (pas `@dev`).

Si vous avez des erreurs de classes manquantes :
```bash
cd tests/e2e/Tester
rm -rf vendor composer.lock
composer install --prefer-stable
```

## 🎉 Résultat

✅ Code plus propre et maintenable
✅ Suppression des redondances
✅ Bootstrap optionnel
✅ Dépendances stables
✅ Namespace sans conflit

