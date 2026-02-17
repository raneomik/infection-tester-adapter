# 🎯 Analyse Finale - Problème Architectural Fondamental

## 💡 Votre suggestion

Injecter le `junit.xml` dans `CoverageRuntime` pour extraire les identifiants de tests.

**Format attendu** (d'après `build/logs/coverage-xml/*.xml`) :
```xml
<covered by="Raneomik\Tests\InfectionTestFramework\Tester\Adapter\TesterAdapterTest::test_it_has_junit_report"/>
```

Format : `<testcase class>::<testcase name>` avec **backslashes**, PAS de points.

## ❌ Pourquoi ça ne peut pas fonctionner

### Ordre d'exécution

1. **Infection démarre** les tests initiaux
2. **`coverage_prepend.php` est chargé** via `auto_prepend_file`
3. **`CoverageRuntime::start()` est appelé** immédiatement
4. **À CE MOMENT** : Le fichier `junit.xml` **N'EXISTE PAS ENCORE** ! 📍
5. Les tests s'exécutent
6. Tester génère le `junit.xml`
7. Les tests se terminent
8. Infection lit la couverture → **TROP TARD pour modifier quoi que ce soit**

### Log de debug confirme

```
First 3 IDs:  [VIDE !]
No match found in backtrace
```

Le JUnit XML est vide car le fichier n'existe pas au moment où on essaie de le lire.

## 🔄 Pourquoi le post-traitement ne fonctionne pas non plus

Le post-traitement dans `CoverageMerger::merge()` arrive aussi trop tard :

1. Infection lit les fichiers XML **PENDANT** l'exécution des tests
2. Il trouve `<covered by="all-tests"/>`
3. Il essaie de résoudre "all-tests" comme FQCN → **ERREUR**
4. `CoverageMerger::merge()` est appelé après
5. Même si on modifie les fichiers, Infection les a déjà lus

## 🎯 Solutions réellement possibles

### Option A : Modifier Tester pour hooks par test ⚠️

Créer un custom `TestCase` base qui :
- Override `setUp()` : Démarre la couverture avec `ClassName::methodName`
- Override `tearDown()` : Arrête et sauvegarde la couverture

**Problème** : Vous avez dit "garder le fonctionnement nominal de Tester"

### Option B : Format Clover uniquement 📊

Utiliser **uniquement** le format Clover qui ne nécessite pas d'IDs de tests.

**Problème** : Infection a besoin de `index.xml` et génère parfois quand même du PHPUnit XML

### Option C : Wrapper autour de Tester 🔧

Créer un wrapper qui :
1. Exécute chaque fichier de test séparément
2. Génère le `junit.xml` AVANT chaque exécution
3. Chaque exécution démarre la couverture avec le bon ID

**Problème** : Très complexe, change complètement le workflow

### Option D : Accepter la limitation 📝

Documenter que l'intégration Tester+Infection ne supporte pas le mapping précis test→couverture.

## 📊 État actuel

| Composant | Status |
|-----------|--------|
| JUnitFormatter | ✅ Fonctionne parfaitement |
| CoverageMerger | ✅ Fonctionne |
| Détection auto test | ❌ Impossible (fichier pas encore créé) |
| Post-traitement | ❌ Trop tard (Infection a déjà lu) |
| Format avec backslashes | ✅ Implémenté correctement |

## 🎓 Conclusion technique

Le problème est **architectural** :

- `php-code-coverage` nécessite l'ID de test au moment de `start()`
- À ce moment, le JUnit XML n'existe pas encore
- Le backtrace ne peut pas détecter le test car le prepend est chargé avant les tests
- Le post-traitement arrive trop tard

**Pour que ça fonctionne**, il faudrait que :
1. Tester génère le JUnit XML **AVANT** d'exécuter les tests (impossible)
2. Ou qu'on puisse démarrer/arrêter la couverture pour chaque test (nécessite hooks dans Tester)
3. Ou qu'Infection ne lise pas la couverture pendant l'exécution (impossible, c'est son fonctionnement)

---

**Date** : 16 février 2026
**Status** : ⚠️ **Limitation architecturale fondamentale identifiée**
**Recommandation** : Option D (documenter) ou Option A (custom TestCase avec hooks)

