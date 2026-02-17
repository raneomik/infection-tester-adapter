# 📊 ÉTAT ACTUEL - Mapping des méthodes de test

## ✅ Ce qui fonctionne

- **Tests e2e** : PASSED ✅
- **Covered Code MSI** : 100% ✅
- **Performance** : ~2s pour 45 mutations ⚡
- **Détection des tests** : `get_included_files()` fonctionne
- **JUnit normalisé** : `JUnitFormatter` ajoute bien `class` et `classname`

## ❌ Ce qui ne fonctionne PAS encore

**Le mapping `Class::run` → vraies méthodes ne se fait pas.**

Preuve : Le XML de couverture contient toujours `<covered by="App\Tests\unit\Covered\CalculatorTest::run"/>` au lieu des méthodes individuelles.

## 🔍 Diagnostic

### Code implémenté

1. **`buildTestMethodMapping()`** - Lit le JUnit normalisé et construit le mapping
2. **`replaceRunWithRealMethods()`** - Remplace dans le XML de couverture
3. **Debug logs** ajoutés pour tracer l'exécution

### Problème suspecté

Le mapping retourne probablement un tableau vide. Causes possibles :

1. **Le JUnit n'existe pas encore** au moment du `merge()` ?
2. **Le JUnit n'est pas normalisé** avant d'être lu ?
3. **Les attributs ne matchent pas** (problème de format) ?
4. **Une exception est levée** et catchée silencieusement ?

### Try/catch masque les erreurs

```php
try {
    CoverageMerger::merge(...);
} catch (Throwable $e) {
    // Erreur silencieuse !
    fwrite(STDERR, 'Warning: ...');
}
```

## 🐛 Actions de debug nécessaires

### 1. Vérifier que buildTestMethodMapping() est appelé

Ajouter un log au début de la méthode :
```php
file_put_contents('/tmp/mapping-called.txt', 'buildTestMethodMapping CALLED');
```

### 2. Vérifier le contenu du JUnit normalisé

Copier le JUnit quelque part avant le mapping :
```php
copy($junitPath, '/tmp/junit-for-debug.xml');
```

### 3. Vérifier que le mapping n'est pas vide

```php
file_put_contents('/tmp/mapping-result.txt', print_r($mapping, true));
```

### 4. Vérifier que replaceRunWithRealMethods() est appelé

Ajouter un log :
```php
file_put_contents('/tmp/replace-called.txt', 'replaceRunWithRealMethods CALLED with ' . count($testMapping) . ' entries');
```

## 💡 Solution alternative si ça ne marche pas

Si le post-processing est trop compliqué à débugger :

### Option A : Accepter `::run` comme ID
- C'est ce qui fonctionne actuellement
- **MSI 100%** - Infection est content
- Pas de granularité méthode par méthode, mais c'est OK pour Tester

### Option B : Parser le fichier de test
Modifier `extractTestIdFromFile()` pour :
1. Parser les assertions dans le fichier
2. Générer un ID comme `Class::test_line_42`
3. Plus complexe mais pas de dépendance au JUnit

## 📊 Conclusion actuelle

**Le code fonctionne avec `::run`** :
- ✅ Tests passent
- ✅ MSI 100%
- ✅ Performance OK

**Le mapping vers les vraies méthodes est implémenté mais ne fonctionne pas encore** :
- ❌ Retourne probablement un tableau vide
- ❌ Besoin de debug pour identifier pourquoi
- ❌ Les logs ne sont pas accessibles (problème terminal)

## 🎯 Recommandation

**Pour l'instant : garder `::run`** car ça fonctionne.

**Pour debug le mapping** :
1. Ajouter des logs dans `/tmp/` (pas dans var/)
2. Vérifier avec `--debug` qu'Infection garde les fichiers
3. Lire les logs après l'exécution

---

**Date** : 16 février 2026
**Statut** : ✅ Fonctionnel avec `::run` | ⚠️ Mapping en cours de debug
**MSI** : 100% | **Tests** : PASS

