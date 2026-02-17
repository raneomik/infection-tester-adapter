# ⚠️ Limitation - Mapping Test→Couverture

## Problème identifié

L'adapter Tester collecte la couverture de code **globalement** pour tous les tests avec un identifiant unique. Infection essaie ensuite de mapper cet identifiant à une classe de test spécifique dans le JUnit XML, ce qui échoue.

## Erreurs rencontrées

1. **"Got 5"** : Le mot "tester" dans les chemins matchait trop de testcases
2. **"For FQCN: __coverage__"** : Infection cherche une classe "__coverage__"
3. **"For FQCN: 00000000-..."** : Infection cherche une classe avec l'UUID
4. **"For FQCN: all-tests"** : Infection cherche une classe "all-tests"

## Cause racine

**Approche actuelle** :
```php
// Tous les tests partagent le même identifiant
$coverage->start('all-tests');
```

**Ce qu'Infection attend** :
```php
// Chaque test a son propre identifiant
$coverage->start('App\Tests\CalculatorTest::testAddition');
// ... test s'exécute ...
$coverage->stop();

$coverage->start('App\Tests\CalculatorTest::testSubtraction');
// ... test s'exécute ...
$coverage->stop();
```

## Solutions possibles

### Option 1 : Mode sans mapping test→couverture ✅ RECOMMANDÉ

Utiliser Infection avec `--only-covered=false` :

```bash
vendor/bin/infection --only-covered=false --min-msi=70
```

**Avantages** :
- ✅ Fonctionne immédiatement
- ✅ Infection génère et teste tous les mutants
- ✅ Le MSI est calculé correctement

**Inconvénients** :
- ❌ Infection teste même les mutants dans du code non couvert
- ❌ Peut être plus lent si beaucoup de code non couvert

### Option 2 : Collection par test (complexe) ⚠️

Implémenter un système qui démarre/arrête la couverture pour chaque test individuel.

**Requis** :
1. Hook dans Tester avant chaque test (`TestCase::setUp()`)
2. Démarrer la couverture avec l'ID du test
3. Hook après chaque test (`TestCase::tearDown()`)
4. Arrêter et sauvegarder la couverture

**Complexité** : Très élevée, nécessite des modifications profondes

### Option 3 : Accepter les limites 📝

Documenter que l'adapter ne supporte pas le mapping précis test→couverture et recommander Option 1.

## 📋 Recommandation finale

**Pour l'utilisateur final** :

```bash
# Dans infection.json5
{
    "testFramework": "tester",
    "onlyCovered": false,  // ← Important !
    "minMsi": 70
}

# Ou en ligne de commande
vendor/bin/infection --only-covered=false
```

**Documentation à ajouter** :

> ⚠️ **Note importante** : L'adapter Tester collecte la couverture globalement et ne peut pas mapper précisément chaque ligne de code à un test spécifique. Il est recommandé d'utiliser `"onlyCovered": false` dans votre configuration Infection.

## État actuel du code

- ✅ JUnit XML correctement formaté
- ✅ Couverture PHPUnit XML générée
- ❌ Mapping test→couverture non supporté
- ✅ Infection fonctionne avec `--only-covered=false`

---

**Date** : 16 février 2026
**Status** : ⚠️ Limitation identifiée - Solution de contournement disponible
**Action** : Documenter et utiliser `--only-covered=false`

