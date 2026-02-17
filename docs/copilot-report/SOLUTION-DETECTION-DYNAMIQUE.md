# 🎯 Solution Finale - Détection Dynamique de l'Identifiant de Test

## 💡 L'idée clé

Utiliser `debug_backtrace()` dans `CoverageRuntime::start()` pour **détecter automatiquement** le test en cours d'exécution et utiliser son identifiant complet.

## 🔧 Implémentation

```php
// CoverageRuntime::detectTestIdentifier()
$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

foreach ($trace as $frame) {
    if (isset($frame['class'], $frame['function']) &&
        (str_starts_with($frame['function'], 'test') || $frame['function'] === 'run')) {

        $class = $frame['class'];  // Ex: App\Tests\unit\Covered\CalculatorTest
        $method = $frame['function'];  // Ex: testAddition

        // Format Infection: "App.Tests.unit.Covered.CalculatorTest::testAddition"
        return str_replace('\\', '.', $class) . '::' . $method;
    }
}

return 'global-coverage';  // Fallback
```

## ✅ Avantages

1. **Pas de hooks nécessaires** : Fonctionne automatiquement
2. **Un identifiant par test** : Chaque test a son propre ID
3. **Format compatible Infection** : `Namespace.Class::method` avec points
4. **Pas de refactoring des tests** : Aucune modification des fichiers de test requis

## 📊 Format généré

**Avant** (problématique) :
```xml
<covered by="all-tests"/>
```

**Après** (correct) :
```xml
<covered by="App.Tests.unit.Covered.CalculatorTest::testAddition"/>
<covered by="App.Tests.unit.Covered.CalculatorTest::testSubtraction"/>
...
```

## 🎯 Correspondance avec JUnit XML

Le JUnit XML contient :
```xml
<testcase
    class="App\Tests\unit\Covered\CalculatorTest"
    classname="App.Tests.unit.Covered.CalculatorTest"  ← Avec points !
    name="testAddition"/>
```

L'identifiant de couverture devient :
```
App.Tests.unit.Covered.CalculatorTest::testAddition
```

Ce qui correspond exactement à `<testcase classname>::<testcase name>` !

## 🔍 Comment ça fonctionne

1. **Tester exécute** un test (ex: `CalculatorTest::testAddition`)
2. **CoverageRuntime démarre** via `coverage_prepend.php`
3. **debug_backtrace()** trouve la méthode de test dans la pile d'appels
4. **Identifiant extrait** : `App.Tests.unit.Covered.CalculatorTest::testAddition`
5. **Couverture démarrée** avec cet ID unique
6. **Infection peut mapper** le test à la couverture !

## ⚡ Résultat attendu

- ✅ Infection trouve les tests dans le JUnit XML
- ✅ Infection trouve la couverture avec les bons IDs
- ✅ Pas d'erreur "For FQCN: xxx"
- ✅ MSI calculé correctement

---

**Date** : 16 février 2026
**Status** : 🟢 Solution implémentée - Test en cours
**Innovation** : Détection automatique via backtrace

