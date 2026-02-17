# ✅ Réponses à tes Questions + Actions

## 1. ❓ Modifications Infection qui restent après son passage

### Problème
Les fichiers `.infection.bak.{hash}` restent si un test plante avant la restoration.

### ✅ Solution appliquée
Ajout d'un `trap` bash dans le wrapper mutant :

```bash
trap 'cleanup' EXIT

cleanup() {
  [ -f "original.bak" ] && mv -f "original.bak" "original" || true
}
```

**Résultat** : Le cleanup est **TOUJOURS** exécuté, même en cas d'erreur.

### Fichier modifié
- `src/TesterAdapter.php` (lignes 300-304)

---

## 2. ❓ Performances très lentes

### Problème actuel
Pour chaque mutant :
```bash
cp original original.bak     # I/O disque
cp mutant original          # I/O disque
run test                    # OK
mv original.bak original    # I/O disque
```

**× 100 mutants** = 300 opérations I/O = **TRÈS LENT**

### 🚀 Solution recommandée : Utiliser IncludeInterceptor

Au lieu de copier physiquement, utiliser le stream wrapper d'Infection :

```php
// Actuellement : copie physique (LENT)
cp /project/src/Calculator.php /project/src/Calculator.php.bak
cp /tmp/mutant_abc.php /project/src/Calculator.php

// Proposé : swap en mémoire (RAPIDE)
IncludeInterceptor::intercept(
    '/project/src/Calculator.php',  // original
    '/tmp/mutant_abc.php'            // mutant
);
IncludeInterceptor::enable();
// Quand PHP charge Calculator.php, il charge en fait mutant_abc.php
```

**Gain estimé** : **10-50x plus rapide** 🚀

### Action requise
Refactorer `TesterAdapter::getMutantCommandLine()` pour utiliser `IncludeInterceptor` au lieu de wrapper bash avec copie.

**Temps estimé** : 2-3h

---

## 3. ❓ Scripts dans les classes = pas PSR-4 friendly

### Problème
`Preprocessor::prepareJobScripts()` génère dynamiquement du code PHP :

```php
$setupContent = <<<'PHP'
<?php
\Infection\TestFramework\Tester\Resources\JobSetup::configure($runner, ...);
PHP;
file_put_contents('tester_job_setup.php', $setupContent);
```

**Problèmes** :
- ❌ Pas de vraies classes
- ❌ Pas testable unitairement
- ❌ Pas d'autocompletion IDE
- ❌ Difficile à maintenir

### ✅ Solution PSR-4

Créer de vraies classes :

```
src/Coverage/
  ├── TesterSetupRunner.php      # Exécutable via --setup
  ├── CoveragePrependRunner.php  # Exécutable via auto_prepend
  ├── FragmentCollector.php
  └── FragmentMerger.php
```

**Utilisation** :

```php
// tester_job_setup.php devient :
<?php
require __DIR__ . '/../../vendor/autoload.php';
exit(\Infection\TesterAdapter\Coverage\TesterSetupRunner::run());
```

**Avantages** :
- ✅ Vraies classes PSR-4
- ✅ Testables unitairement
- ✅ Autocompletion IDE
- ✅ Type safety

**Temps estimé** : 1 jour

---

## 4. ❓ Monorepo + séparer fonctionnalités

### Problème actuel
Tout est mélangé dans `tester-adapter` :
- Adapter Infection ↔ Tester
- Collecte de couverture
- Merge de fragments
- Normalisation JUnit

### 🎯 Solution : Monorepo avec packages séparés

```
libs/infection/
├── tester-adapter/          # Adapter principal (léger)
│   ├── src/
│   │   ├── TesterAdapter.php
│   │   ├── TesterAdapterFactory.php
│   │   └── CommandLineBuilder.php
│   └── composer.json
│
└── tester-coverage/         # Extension couverture (NOUVEAU)
    ├── src/
    │   ├── Setup/
    │   │   └── TesterSetupRunner.php
    │   ├── Collection/
    │   │   ├── FragmentCollector.php
    │   │   ├── DriverFactory.php
    │   │   └── Drivers/
    │   │       ├── PcovDriver.php
    │   │       ├── PhpdbgDriver.php
    │   │       └── XdebugDriver.php
    │   ├── Merge/
    │   │   ├── FragmentMerger.php
    │   │   └── JUnitNormalizer.php
    │   └── CoverageExtension.php
    └── composer.json
```

**Dépendances** :

```json
// tester-adapter/composer.json
{
  "require": {
    "infection/tester-coverage": "^1.0"
  }
}

// tester-coverage/composer.json
{
  "name": "infection/tester-coverage",
  "description": "Code coverage for Nette Tester",
  "require": {
    "nette/tester": "^2.6",
    "phpunit/php-code-coverage": "^11.0"
  }
}
```

**Avantages** :
- ✅ Séparation des responsabilités
- ✅ `tester-coverage` réutilisable par d'autres projets
- ✅ Versioning indépendant
- ✅ Tests séparés
- ✅ Plus facile à maintenir

**Temps estimé** : 2-3 jours

---

## 📋 Plan d'action recommandé

### Phase 1 : Fix urgent (✅ FAIT)
- [x] Trap bash pour nettoyage automatique

### Phase 2 : Performances (2-3h)
- [ ] Utiliser `IncludeInterceptor` au lieu de copier fichiers
- [ ] Benchmark avant/après
- [ ] Tests

### Phase 3 : PSR-4 pur (1 jour)
- [ ] Créer `src/Coverage/` avec vraies classes
- [ ] Supprimer génération dynamique de scripts
- [ ] Tests unitaires
- [ ] Documentation

### Phase 4 : Monorepo (2-3 jours)
- [ ] Créer structure `tester-coverage/`
- [ ] Migrer code couverture
- [ ] Setup composer workspaces/symlinks
- [ ] Tests pour chaque package
- [ ] CI/CD

---

## 💡 Recommandation

**Option A** : Fix urgent uniquement (✅ fait) + doc des prochaines étapes

**Option B** : Fix + Performances (Phase 1-2) = **2-4h**

**Option C** : Fix + Perfs + PSR-4 (Phase 1-3) = **1-2 jours**

**Option D** : Tout faire (Phase 1-4) = **3-4 jours**

---

## 🎯 Ma recommandation perso

**Faire Phase 1-2 maintenant** (fix + perfs) car :
- ✅ Fix critique (nettoyage) déjà fait
- ✅ Performances = gros impact utilisateur
- ✅ Rapide à implémenter (2-3h)
- ✅ Pas de breaking change

**Phase 3-4 plus tard** car :
- Refacto PSR-4 = breaking change potentiel
- Monorepo = gros changement d'architecture
- Peut attendre la prochaine version majeure

---

## ❓ Ta décision ?

Que veux-tu que je fasse maintenant ?

**A)** Arrêter ici (fix nettoyage fait)
**B)** Continuer avec performances (IncludeInterceptor)
**C)** Faire le refacto PSR-4 complet
**D)** Tout refaire en monorepo

Dis-moi et je continue ! 🚀

