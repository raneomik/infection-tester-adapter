# 🔍 Diagnostic - Mapping des méthodes de test

## ❓ Problème potentiel

Le XML de couverture contient toujours `Class::run` au lieu des vraies méthodes de test.

## 🔧 Ce qui a été implémenté

1. **`buildTestMethodMapping()`** - Construit le mapping depuis le JUnit normalisé
2. **`replaceRunWithRealMethods()`** - Remplace dans les XML de couverture

## 🐛 Points à vérifier

### 1. Le mapping est-il créé ?

Vérifier que `buildTestMethodMapping()` retourne bien quelque chose comme :
```php
[
    "App\Tests\unit\Covered\CalculatorTest::run" => [
        "App.Tests.unit.Covered.CalculatorTest::testAddition",
        "App.Tests.unit.Covered.CalculatorTest::testDivision",
        ...
    ]
]
```

### 2. Les fichiers XML sont-ils trouvés ?

Le glob cherche dans `$outDir/*.xml` et `$outDir/*/*.xml`.
Les fichiers de couverture sont-ils à ces emplacements ?

### 3. Le matching fonctionne-t-il ?

Le XML contient : `<covered by="App\Tests\unit\Covered\CalculatorTest::run"/>`
Le mapping a la clé : `App\Tests\unit\Covered\CalculatorTest::run`

Est-ce que `$byAttr === $runId` matche ?

### 4. Logs de debug

Activer les logs de debug dans `replaceRunWithRealMethods()` pour voir :
- Combien d'entrées dans le mapping
- Combien de fichiers XML trouvés
- Combien de nodes `<covered>` trouvés
- Combien de matches

## 💡 Solution alternative si le mapping ne marche pas

Si le post-processing XML est trop compliqué, une alternative serait de :
1. **Modifier `extractTestIdFromFile()`** pour retourner TOUTES les méthodes de test d'un fichier
2. Au lieu de retourner `Class::run`, retourner `Class::testMethod1` (la première trouvée)
3. Mais cela nécessiterait de parser les assertions dans le fichier de test

## 📊 Tests à faire

1. Lancer Infection et garder le XML généré
2. Regarder le contenu de `var/infection/infection/Covered/*.xml`
3. Vérifier s'il contient `::run` ou les vraies méthodes

---

**Statut** : En investigation
**Besoin** : Voir le contenu exact du XML généré pour diagnostiquer

