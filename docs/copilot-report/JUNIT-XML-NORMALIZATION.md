# Formatage du JUnit XML pour Infection

## Problème initial

Lors de l'utilisation d'Infection avec le Tester adapter, le format JUnit XML généré par Tester n'était pas compatible avec les attentes d'Infection et du format PHPUnit standard.

## Solution : JUnitFormatter

Un service dédié `JUnitFormatter` transforme automatiquement le format Tester en format PHPUnit compatible.

### Format Tester (AVANT)

```xml
<testsuites>
    <testsuite tests="7">
        <testcase classname="/path/Test.php method=testMethod" name="/path/Test.php method=testMethod"/>
        <testcase classname="/path/Test.php method=testOther" name="/path/Test.php method=testOther"/>
        <testcase classname="/path/OtherTest.php method=testFoo" name="/path/OtherTest.php method=testFoo"/>
    </testsuite>
</testsuites>
```

**Caractéristiques** :
- ❌ Structure plate (tous les testcases au même niveau)
- ❌ `classname` contient chemin + méthode
- ❌ Pas de regroupement par classe de test

### Format PHPUnit (APRÈS)

```xml
<testsuites>
    <testsuite name="Tester Test Suite" tests="7">
        <testsuite name="App\Tests\TestClass" file="/path/Test.php" tests="2">
            <testcase name="testMethod" class="App\Tests\TestClass" classname="App\Tests\TestClass" file="/path/Test.php"/>
            <testcase name="testOther" class="App\Tests\TestClass" classname="App\Tests\TestClass" file="/path/Test.php"/>
        </testsuite>
        <testsuite name="App\Tests\OtherTest" file="/path/OtherTest.php" tests="1">
            <testcase name="testFoo" class="App\Tests\OtherTest" classname="App\Tests\OtherTest" file="/path/OtherTest.php"/>
        </testsuite>
    </testsuite>
</testsuites>
```

**Caractéristiques** :
- ✅ Structure hiérarchique (testsuite par classe)
- ✅ `name` contient uniquement le nom de la méthode
- ✅ `classname` = namespace complet **SANS remplacement des backslashes par des points**
- ✅ Regroupement logique par classe de test

## Implémentation

### Service JUnitFormatter

Le service `JUnitFormatter` effectue les transformations suivantes :

1. **Extraction** : Parse le format Tester `/path/Test.php method=testMethod`
2. **Analyse de fichier** : Lit les fichiers PHP pour extraire namespace et classe
3. **Regroupement** : Groupe les testcases par classe
4. **Restructuration** : Crée une hiérarchie testsuite > testsuite > testcase

### Code principal

```php
// Utilisation dans CoverageMerger
JUnitFormatter::format($junitPath);
```

### Points clés

- **Namespace préservé** : `App\Tests\TestClass` (backslashes conservés)
- **Pas de conversion en points** : On garde `\` au lieu de `.`
- **Structure hiérarchique** : Conforme au format PHPUnit
- **Testsuite par TestCase** : Chaque classe de test a sa propre testsuite

## Fichiers modifiés

- **`src/Coverage/JUnitFormatter.php`** : Nouveau service dédié
- **`src/Coverage/CoverageMerger.php`** : Utilise JUnitFormatter au lieu de l'ancienne normalisation

## Test

Pour tester le formatage :

```bash
cd tests/e2e/Tester

# Générer un junit.xml
vendor/bin/tester tests/unit -o junit:var/test.xml

# Le formater
php format-test.php

# Vérifier le résultat
cat var/test-formatted-result.xml
```

## Avantages

✅ Format conforme à PHPUnit
✅ Compatible avec Infection
✅ Structure hiérarchique claire
✅ Namespace complets préservés
✅ Regroupement logique par classe
✅ Code propre et maintenable (service dédié)

## Date

15 février 2026

