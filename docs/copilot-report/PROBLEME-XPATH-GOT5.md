# Problème XPath "Got 5" - Analyse

## Erreur
```
Expected the query "//testcase[contains(@file, "tester")][1]" to return a "DOMNodeList" with no or one node. Got "5".
```

## Cause

Infection cherche des testcases en utilisant plusieurs requêtes XPath dans l'ordre :

1. `//testsuite[@name="FQCN"]` - Cherche une testsuite ✅ Fonctionne
2. `//testcase[@class="FQCN"]` - Cherche un testcase ✅ Fonctionne
3. `//testcase[contains(@file, "partial")]` - **PROBLÈME** ❌

La requête #3 est une **fallback** utilisée quand les #1 et #2 ne trouvent rien.

## Pourquoi la requête #3 est appelée ?

Infection cherche la classe **SOURCE** (ex: `App\SourceClass`), pas la classe de **TEST** (ex: `App\Tests\unit\SourceClassTest`).

Quand il cherche `App\SourceClass`, les requêtes #1 et #2 ne trouvent rien (car le JUnit XML ne contient que les classes de TEST), donc il fallback sur la requête #3.

La requête #3 transforme `App\SourceClass` en cherchant juste "Source" ou "SourceClass", et cherche `//testcase[contains(@file, "SourceClass")]`.

Mais comme **TOUS nos chemins** contiennent `/tester-adapter/tests/`, quand Infection cherche juste "tester", il trouve 5 testcases au lieu d'un seul !

## Solution

Il faut que les identifiants de tests dans la **couverture XML** correspondent exactement aux identifiants dans le **JUnit XML**.

Actuellement :
- Couverture : `<covered by="App.Tests.unit.SourceClassTest::testAddition"/>`
- JUnit : `<testcase classname="App.Tests.unit.SourceClassTest" name="testAddition"/>`

Infection doit pouvoir trouver le test via `//testcase[@classname="App.Tests.unit.SourceClassTest"]` + `[@name="testAddition"]`.

Le problème est qu'Infection cherche peut-être avec un format différent ou que le mappage couverture→junit ne fonctionne pas.

## Actions nécessaires

1. ✅ Post-traiter les fichiers XML de couverture pour remplacer `<covered by="tester"/>` par les vrais IDs
2. ❌ Vérifier le format exact des IDs de tests utilisés par Infection
3. ❌ S'assurer que les requêtes #1 ou #2 trouvent toujours un résultat AVANT la requête #3

## État actuel

- JUnit XML : ✅ Format correct
- Couverture XML : ⚠️ IDs de tests remplacés mais format peut-être incorrect
- Intégration : ❌ Requête XPath #3 toujours appelée et trouve 5 résultats

---

Date: 16 février 2026

