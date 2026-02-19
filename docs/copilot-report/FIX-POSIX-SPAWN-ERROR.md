# ✅ FIX FINAL - proc_open(): posix_spawn() failed: Bad file descriptor

**Date:** 2026-02-19  
**Problème:** `Error: proc_open(): posix_spawn() failed: Bad file descriptor`  
**Cause:** `vendor/bin/tester` est un wrapper Composer qui ne peut pas être passé directement à `php`  
**Status:** ✅ **RÉSOLU via simplification** (voir `SIMPLIFICATION-COMMAND-BUILDER.md`)

## 🔍 Diagnostic

### Ce qui était exécuté
```bash
/usr/bin/php8.5 \
  -d pcov.enabled=1 \
  -d auto_prepend_file=.../bootstrap-mutant-XXX.php \
  /path/vendor/bin/tester \  # ← PROBLÈME ICI
  -o junit:... \
  /tests/TestCase/Covered/CalculatorTest.php
```

### Le problème
`vendor/bin/tester` est un **wrapper Composer** généré automatiquement :
- Commence par `#!/usr/bin/env php`
- Contient du code PHP pour charger le vrai script
- **Ne peut PAS être passé comme argument à `php`**

Quand PHP essaie d'interpréter ce wrapper, il échoue avec `posix_spawn() failed: Bad file descriptor`.

## ✅ Solution FINALE (simplification)

Au lieu de passer `vendor/bin/tester` à PHP, **utiliser les options natives de Tester** :
- `-p <path>` pour spécifier l'interpréteur PHP
- `-d <key=value>` pour définir les directives INI

### Nouvelle commande
```bash
/path/vendor/bin/tester \
  -p /usr/bin/php8.5 \
  -d pcov.enabled=1 \
  -d auto_prepend_file=.../bootstrap-mutant-XXX.php \
  -o junit:... \
  /tests/TestCase/Covered/CalculatorTest.php
```

### Avantages
- ✅ **Plus de problème de wrapper** - Tester gère lui-même l'exécution de PHP
- ✅ **Plus simple** - Pas besoin de résoudre `tester.php`
- ✅ **Plus robuste** - Utilise les capacités natives de Tester
- ✅ **Moins de code** - `CommandLineBuilder` réduit de 145 → 60 lignes

## 📝 Fichiers modifiés

### `src/Command/CommandLineBuilder.php`
- **Avant:** 145 lignes avec logique complexe
- **Après:** ~60 lignes avec logique simple
- Supprimé: gestion batch files, phpdbg, caching, résolution tester.php

### `src/TesterAdapter.php`
- Supprimé: résolution de `tester.php` depuis `vendor/bin/tester`
- Simplifié: appel direct à `CommandLineBuilder::build()`

## 🎯 Impact

Cette solution est **encore meilleure** que le workaround initial car :
1. Elle suit les conventions de Tester (utilise ses options natives)
2. Elle simplifie drastiquement le code
3. Elle élimine tous les edge cases liés aux wrappers

## 🔗 Documentation complète

Voir **`SIMPLIFICATION-COMMAND-BUILDER.md`** pour les détails complets de cette simplification.

---

**Note historique:** Ce document décrivait initialement un workaround (résolution de `tester.php`), mais la vraie solution était d'utiliser les options natives de Tester. Le workaround a été remplacé par cette approche plus propre et simple.

