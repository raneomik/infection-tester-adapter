# 🔧 Approche Finale - Détection au Shutdown

## 🎯 Le problème identifié

Vous aviez raison : **la détection ne fonctionne pas correctement** car :

1. `auto_prepend_file` s'exécute **AVANT** que le test commence
2. La stack trace au moment du `start()` ne contient que le fichier temporaire
3. `get_included_files()` ne donne que des chemins de fichiers, pas les classes

## ✅ Solution implémentée

### Principe
**Détecter le test ID au moment du shutdown**, quand le test a vraiment été exécuté.

### Flux

```
1. CoverageRuntime::start()
   └─→ CodeCoverage::start('__TEMP__')  // ID temporaire
   └─→ register_shutdown_function()

2. Test s'exécute
   └─→ La classe de test est maintenant dans la stack

3. shutdown_function() appelé
   └─→ dumpCoverageWithRealTestId()
       └─→ detectTestIdentifierFromContext() // MAINTENANT la stack contient le test !
       └─→ Wrapper : ['coverage' => $coverage, 'testId' => $realId]
       └─→ Serialise le wrapper

4. CoverageMerger::loadFragment()
   └─→ Détecte le wrapper
   └─→ Crée un nouveau CodeCoverage avec le vrai test ID
   └─→ Merge les données de l'ancien
   └─→ Retourne le nouveau CodeCoverage avec le bon ID
```

## 📁 Modifications

### CoverageRuntime.php
```php
// Start avec ID temporaire
$coverage->start('__TEMP__');

// Au shutdown, détecte le vrai ID
register_shutdown_function(function() {
    $realTestId = detectTestIdentifierFromContext(); // Stack contient le test !
    $wrapper = ['coverage' => $coverage, 'testId' => $realTestId];
    serialize($wrapper);
});
```

### CoverageMerger.php
```php
// Charge le wrapper
$data = unserialize($fragment);

if (is_array($data) && isset($data['testId'])) {
    // Crée nouveau CodeCoverage avec le vrai test ID
    $newCoverage = new CodeCoverage($driver, $filter);
    $newCoverage->start($data['testId']);  // Vrai ID !
    $newCoverage->merge($data['coverage']);
    return $newCoverage;
}
```

## ❓ Questions restantes

### 1. Le shutdown est-il appelé correctement ?
- À tester : Est-ce que `detectTestIdentifierFromContext()` trouve bien la classe de test dans la stack au shutdown ?

### 2. Le merge fonctionne-t-il ?
- À vérifier : Est-ce que `$newCoverage->merge($oldCoverage)` copie bien toutes les données avec le nouveau test ID ?

### 3. Performance
- Le double CodeCoverage (temp + real) a-t-il un impact ?

## 🧪 Tests à faire

1. **Dump un fragment et regarder son contenu**
   ```bash
   cd tests/e2e/Tester
   vendor/bin/tester tests/
   ls -la var/infection/infection/coverage-fragments/
   php -r "var_dump(unserialize(file_get_contents('var/infection/infection/coverage-fragments/cc-XXX.phpser')));"
   ```

2. **Vérifier que le test ID est détecté**
   - Ajouter un `file_put_contents('/tmp/test-id.log', $realTestId)` dans `dumpCoverageWithRealTestId()`

3. **Vérifier le XML généré**
   ```bash
   cat var/infection/infection/*.xml | grep "covered by"
   ```

## 🎯 Objectif

Voir dans le XML final :
```xml
<covered by="App\Tests\unit\Covered\CalculatorTest::testAddition"/>
```

Au lieu de :
```xml
<covered by="__TEMP__"/>
```

---

**Date** : 16 février 2026
**Statut** : 🔬 Solution implémentée - Tests nécessaires
**Principe** : Détection au shutdown + Wrapper + Recréation CodeCoverage

