# JUnitFormatter - Service de formatage JUnit XML

## Vue d'ensemble

Le `JUnitFormatter` est un service dédié qui transforme le format JUnit XML généré par Tester en un format compatible avec PHPUnit et Infection.

## Problème résolu

Tester génère un JUnit XML avec une **structure plate** où tous les testcases sont au même niveau et où les attributs contiennent à la fois le chemin du fichier et le nom de la méthode :

```xml
<testcase classname="/path/Test.php method=testMethod" name="/path/Test.php method=testMethod"/>
```

Ce format n'est pas compatible avec le format PHPUnit attendu par Infection.

## Transformation

Le `JUnitFormatter` effectue une transformation complète en créant une **structure hiérarchique** :

### Avant (Tester)
```xml
<testsuites>
    <testsuite tests="3">
        <testcase classname="/path/CalculatorTest.php method=testAdd" name="..."/>
        <testcase classname="/path/CalculatorTest.php method=testSub" name="..."/>
        <testcase classname="/path/UserTest.php method=testUser" name="..."/>
    </testsuite>
</testsuites>
```

### Après (PHPUnit)
```xml
<testsuites>
    <testsuite name="Tester Test Suite" tests="3">
        <testsuite name="App\Tests\CalculatorTest" file="/path/CalculatorTest.php" tests="2">
            <testcase name="testAdd" class="App\Tests\CalculatorTest" classname="App\Tests\CalculatorTest"/>
            <testcase name="testSub" class="App\Tests\CalculatorTest" classname="App\Tests\CalculatorTest"/>
        </testsuite>
        <testsuite name="App\Tests\UserTest" file="/path/UserTest.php" tests="1">
            <testcase name="testUser" class="App\Tests\UserTest" classname="App\Tests\UserTest"/>
        </testsuite>
    </testsuite>
</testsuites>
```

## Caractéristiques clés

✅ **Testsuite par TestCase** : Chaque classe de test a sa propre testsuite
✅ **Structure hiérarchique** : testsuite > testsuite > testcase
✅ **Namespace complet** : `App\Tests\TestClass` (backslashes préservés)
✅ **Pas de conversion en points** : `classname` garde les `\` au lieu de `.`
✅ **Attribut name simplifié** : Contient uniquement le nom de la méthode

## Utilisation

### Automatique (dans CoverageMerger)

Le formatage est appliqué automatiquement après la génération du junit.xml :

```php
// Dans CoverageMerger::merge()
JUnitFormatter::format($junitPath);
```

### Manuel

```php
use Raneomik\InfectionTestFramework\Tester\Coverage\JUnitFormatter;

$success = JUnitFormatter::format('/path/to/junit.xml');
```

## Architecture

### Méthodes principales

1. **`format()`** : Point d'entrée, transforme un fichier JUnit XML
2. **`extractTestcases()`** : Extrait tous les testcases du document
3. **`parseTesterFormat()`** : Parse `/path/Test.php method=testMethod`
4. **`extractClassInfo()`** : Lit le fichier PHP pour extraire namespace et classe
5. **`groupTestcasesByClass()`** : Regroupe les tests par classe
6. **`buildPhpUnitStructure()`** : Construit la structure hiérarchique PHPUnit

### Processus de transformation

```
Tester XML
    ↓
extractTestcases() → Parse chaque testcase
    ↓
parseTesterFormat() → Extrait file + method
    ↓
extractClassInfo() → Lit le fichier PHP → Extrait namespace + class
    ↓
groupTestcasesByClass() → Regroupe par classe complète (namespace\class)
    ↓
buildPhpUnitStructure() → Crée la hiérarchie testsuite/testcase
    ↓
PHPUnit XML
```

## Différences avec l'ancienne approche

| Aspect | Ancienne normalisation | JUnitFormatter |
|--------|----------------------|----------------|
| Structure | Plate (modification des attributs) | Hiérarchique (restructuration complète) |
| Testsuite | Un seul niveau | Testsuite par classe |
| classname | Avec points (`App.Tests.Test`) | Avec backslashes (`App\Tests\Test`) |
| Organisation | Code dans CoverageMerger | Service dédié séparé |
| Maintenabilité | Méthodes privées multiples | Classe focalisée |

## Tests

### Script de test

```bash
cd tests/e2e/Tester

# Générer un junit.xml avec Tester
vendor/bin/tester tests/unit -o junit:var/test.xml

# Formater avec le script de test
php format-test.php

# Voir le résultat
cat var/test-formatted-result.xml
```

### Vérifications attendues

- [ ] Structure hiérarchique créée
- [ ] Un testsuite par classe de test
- [ ] Attribut `name` contient uniquement la méthode
- [ ] Attribut `classname` contient le namespace complet avec `\`
- [ ] Attribut `class` identique à `classname`
- [ ] Attribut `file` contient le chemin complet

## Compatibilité

✅ Infection 0.27+
✅ PHPUnit 9+
✅ Tester 2.x
✅ PHP 8.0+

## Références

- Format JUnit XML : https://www.ibm.com/docs/en/developer-for-zos/14.1?topic=formats-junit-xml-format
- PHPUnit JUnit output : https://phpunit.de/manual/current/en/textui.html#textui.junit
- Tester documentation : https://tester.nette.org/

---

**Auteur** : Migration du système de normalisation vers JUnitFormatter
**Date** : 15 février 2026

