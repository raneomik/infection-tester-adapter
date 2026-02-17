# 🔍 PROBLÈME FINAL - Le matching ne se fait pas

## 🐛 Le bug identifié

Le **mapping est créé** mais **le remplacement ne trouve rien**.

### Pourquoi ?

**Les clés du mapping ne matchent pas avec les attributs du XML.**

#### Ce que génère CoverageRuntime (dans le XML)
```xml
<covered by="App\Tests\unit\Covered\CalculatorTest::run"/>
```

#### Ce que contient le mapping (clé)
```php
$runKey = $class . '::run';
// Si $class = "App\Tests\CalculatorTest" (sans le "unit\Covered")
// Alors $runKey = "App\Tests\CalculatorTest::run"
```

#### Le matching
```php
if ($byAttr === $runId) {  // ❌ Ne match JAMAIS !
    // "App\Tests\unit\Covered\CalculatorTest::run" !== "App\Tests\CalculatorTest::run"
}
```

## 💡 Solution

Il faut que **la clé du mapping ait EXACTEMENT le même namespace** que ce qui est dans le XML de couverture.

### Option 1 : Vérifier ce que contient réellement `$class`

Si `$class` contient déjà le namespace complet (`App\Tests\unit\Covered\CalculatorTest`), alors le code devrait marcher.

### Option 2 : Utiliser un matching plus intelligent

Au lieu de `===`, faire un matching par la fin :
```php
if (str_ends_with($byAttr, '::run') && str_contains($byAttr, $class)) {
    // Match !
}
```

### Option 3 : Normaliser les deux côtés

Extraire juste le nom de la classe (sans namespace) et comparer :
```php
$runClass = substr($runId, strrpos($runId, '\\') + 1);
$byClass = substr($byAttr, strrpos($byAttr, '\\') + 1);
if ($runClass === $byClass) {
    // Match !
}
```

## 📊 Debug nécessaire

Pour savoir quelle option choisir, il faut voir :
1. Ce que contient `$class` après normalisation JUnit
2. Ce que contient `$byAttr` dans le XML de couverture

Avec `--debug` et les logs, on devrait voir :
```
Mapping:
  App\Tests\CalculatorTest::run => [...]

XML contains:
  <covered by="App\Tests\unit\Covered\CalculatorTest::run"/>

Match: NO (namespace differs)
```

## ✅ Ce qui fonctionne déjà

- Le mapping est créé ✅
- Le remplacement parcourt les nodes ✅
- `saveXML()` est correct ✅

**Seul le matching `$byAttr === $runId` échoue.**

---

**Action** : Utiliser un matching plus flexible ou vérifier le namespace exact

