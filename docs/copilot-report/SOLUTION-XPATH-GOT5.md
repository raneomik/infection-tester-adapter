# Solution finale au problème "Got 5"

## Problème initial

```
Expected the query "//testcase[contains(@file, "tester")][1]" to return a "DOMNodeList" with no or one node. Got "5".
```

## Cause racine

L'identifiant de couverture `"tester"` dans `CoverageRuntime::start('tester')` causait un conflit :

- Tous les fichiers de test sont dans `/path/tester-adapter/tests/...`
- Quand Infection cherchait `//testcase[contains(@file, "tester")]`, il trouvait **5 testcases** au lieu d'un seul
- La requête XPath matchait à cause du mot "tester" dans le **chemin**, pas dans l'identifiant

## Solution appliquée

1. **Changement de l'identifiant de couverture** :
   ```php
   // Avant
   $coverage->start('tester');

   // Après
   $coverage->start('__coverage__');
   ```

2. **Post-traitement des fichiers XML de couverture** :
   - Remplacement de `<covered by="__coverage__"/>`
   - Par `<covered by="App.Tests.CalculatorTest::testAddition"/>` pour chaque test

3. **Nom de testsuite changé** :
   - Avant : `"Tester Test Suite"`
   - Après : `"Nette Test Suite"`

## Fichiers modifiés

- `src/Script/CoverageRuntime.php` : Identifiant `'__coverage__'` au lieu de `'tester'`
- `src/Coverage/CoverageMerger.php` : Post-traitement pour remplacer les IDs de tests
- `src/Coverage/JUnitFormatter.php` : Nom de testsuite sans "tester"

## Impact

✅ L'identifiant `__coverage__` ne matche **aucun chemin de fichier**
✅ La requête XPath problématique ne trouve **aucun résultat** ou au plus un
✅ Infection peut correctement identifier les tests

---

**Date** : 16 février 2026
**Status** : ✅ Résolu

