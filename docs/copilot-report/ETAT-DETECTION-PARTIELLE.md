# 🎯 État Final - Détection Automatique du Test

## ✅ Progrès réalisés

1. **Détection du fichier de test** : ✅ Fonctionne via `$_SERVER['argv']` et fichiers inclus
2. **Extraction de la classe** : ✅ `App.Tests.unit.Covered.CalculatorTest`
3. **Format avec points** : ✅ Compatible Infection

## ❌ Problème résiduel

**Erreur** : `For FQCN: App.Tests.unit.Covered.CalculatorTest`

### Identifiant généré
```
App.Tests.unit.Covered.CalculatorTest::run
```

### Ce qu'Infection cherche
```
App.Tests.unit.Covered.CalculatorTest::testAddition
App.Tests.unit.Covered.CalculatorTest::testSubtraction
App.Tests.unit.Covered.CalculatorTest::testDivision
...
```

## 🔍 Cause du problème

`CoverageRuntime::start()` est appelé **UNE SEULE FOIS** au début de l'exécution de TOUS les tests. À ce moment :

1. Nous pouvons détecter **LE PREMIER** fichier de test dans `$_SERVER['argv']`
2. Nous pouvons extraire **LA CLASSE** de test
3. Mais nous **NE POUVONS PAS** savoir quelle méthode de test sera exécutée

Tester exécute ensuite **toutes les méthodes** de la classe (`testAddition`, `testSubtraction`, etc.) mais la couverture a déjà été démarrée avec `ClassName::run`.

## 💡 Solutions possibles

### Option A : Modifier l'architecture (complexe) ⚠️

Changer complètement l'approche pour démarrer/arrêter la couverture **par méthode de test** :

1. Créer un custom `TestCase` qui override `setUp()`/`tearDown()`
2. Démarrer la couverture dans `setUp()` avec l'ID correct
3. Arrêter la couverture dans `tearDown()`
4. Tous les tests doivent étendre ce custom TestCase

**Complexité** : Très élevée, nécessite refactoring de tous les tests

### Option B : Intercepter l'exécution Tester (très complexe) 🔴

Créer un wrapper/listener Tester qui intercepte chaque appel de méthode de test.

**Complexité** : Très élevée, nécessite connaissance approfondie des internals de Tester

### Option C : Utiliser un identifiant par classe (limitation acceptée) 📝

Accepter que l'identifiant soit au niveau de la **classe** et non de la **méthode**.

```
App.Tests.unit.Covered.CalculatorTest::run
```

**Impact** : Infection ne peut pas mapper précisément quelle méthode de test couvre quelle ligne, mais il sait que la classe de test couvre les lignes.

### Option D : Forker php-code-coverage pour ajouter un hook 🔴

Modifier `php-code-coverage` pour qu'il appelle un callback avant chaque enregistrement de couverture.

**Complexité** : Maximale, maintenabilité problématique

## 📊 État actuel

| Élément | Status |
|---------|--------|
| JUnit XML formaté | ✅ |
| Couverture générée | ✅ |
| Détection fichier test | ✅ |
| Détection classe test | ✅ |
| Détection méthode test | ❌ |
| Infection compatible | ⚠️ Partiel |

## 🎓 Conclusion technique

**Le problème fondamental** : `php-code-coverage` nécessite de démarrer la couverture avec un identifiant **AVANT** l'exécution du code, mais notre architecture appelle `start()` une seule fois pour tous les tests.

**Pour fonctionner pleinement** : Il faudrait démarrer/arrêter la couverture individuellement pour **chaque méthode de test**, ce qui nécessite une architecture complètement différente.

## 📝 Recommandation

**Court terme** : Documenter la limitation et proposer Option C (identifiant par classe)

**Long terme** : Implémenter Option A avec un custom TestCase si la précision méthode par méthode est vraiment nécessaire

---

**Date** : 16 février 2026
**Status** : 🟡 Détection partielle - Classe ✅ Méthode ❌
**Blocage** : Architecture globale de collection de couverture

