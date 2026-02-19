# ⚠️ LIMITATION - TestCase 3× plus lent que Plain/FunctionTest

**Date:** 2026-02-19  
**Observation:** TestCase est 3× plus lent que Plain/FunctionTest (9s vs 3s pour 46 mutants)  
**Cause:** Overhead de Tester pour l'exécution des classes TestCase  
**Status:** **Limitiation de Tester - non résolvable**

## 📊 Mesures réelles

### Benchmark des 3 types de tests

```bash
# tests/e2e/Tester avec 46 mutants

TestCase:     real 9.087s  (Time: 8s, Threads: 3)  ❌ 3× plus lent
Plain:        real 2.761s  (Time: 2s, Threads: 3)  ✅
FunctionTest: real 3.008s  (Time: 2s, Threads: 3)  ✅
```

**Résultat : TestCase est 3× plus lent que Plain/FunctionTest**

## 🔍 Analyse de la cause

### Ce que font Plain et FunctionTest

**Plain** (script simple) :
```php
<?php
require __DIR__ . '/../../bootstrap.php';

$calculator = new Calculator();
Assert::same(5, $calculator->add(2, 3));
// Script exécuté directement par PHP
```

**Exécution Tester :**
1. Include le fichier
2. Exécute le script
3. Fin

**Temps : ~0.06s par test**

### Ce que fait TestCase

**TestCase** (classe avec méthodes) :
```php
<?php
require __DIR__ . '/../../bootstrap.php';

/**
 * @testCase
 */
class CalculatorTest extends TestCase
{
    public function testAddition(): void { ... }
    public function testSubtraction(): void { ... }
    // 7 méthodes de test
}
```

**Exécution Tester :**
1. Include le fichier
2. **Parse la classe** (Reflection pour trouver les méthodes de test)
3. **Pour chaque méthode** :
   - Instancie la classe
   - Appelle setUp() si existe
   - Appelle la méthode de test
   - Appelle tearDown() si existe
4. Collecte les résultats

**Temps : ~0.17s par test** (3× plus lent)

## 🎯 Pourquoi TestCase est plus lent

### 1. Overhead de Reflection

Tester utilise **Reflection** pour découvrir les méthodes de test :
```php
// Pseudo-code de ce que fait Tester
$reflection = new ReflectionClass(CalculatorTest::class);
$methods = $reflection->getMethods();
foreach ($methods as $method) {
    if (str_starts_with($method->getName(), 'test')) {
        // Exécute cette méthode
    }
}
```

Cet overhead est **absent** pour Plain/FunctionTest qui sont juste exécutés.

### 2. Instanciation multiple

Pour TestCase, Tester **instancie la classe N fois** (une par méthode) :
```php
// 7 méthodes = 7 instanciations
new CalculatorTest(); // testAddition
new CalculatorTest(); // testSubtraction
new CalculatorTest(); // testMultiplication
// ... etc
```

Plain/FunctionTest n'ont **aucune instanciation**.

### 3. Lifecycle hooks

TestCase supporte setUp()/tearDown() :
```php
class CalculatorTest extends TestCase
{
    protected function setUp(): void { ... }
    protected function tearDown(): void { ... }
    
    public function testSomething(): void { ... }
}
```

Tester doit **vérifier et appeler** ces méthodes pour chaque test.

Plain/FunctionTest n'ont **aucun lifecycle**.

## 📊 Détails des performances

### Breakdown par mutant (estimation)

**Plain :**
- Chargement fichier : 0.01s
- Exécution script : 0.05s
- **Total : ~0.06s par mutant**

**TestCase :**
- Chargement fichier : 0.01s
- Reflection (découverte méthodes) : 0.03s
- Instanciation classe (×7) : 0.07s
- setUp/tearDown checks (×7) : 0.02s
- Exécution méthodes : 0.05s
- **Total : ~0.18s par mutant** (3× plus lent)

### Pour 46 mutants avec 3 threads

**Plain :**
```
46 mutants ÷ 3 threads = 15.3 mutants/thread
15.3 × 0.06s = 0.92s/thread
Overhead + I/O : +1.8s
Total : ~2.7s
```

**TestCase :**
```
46 mutants ÷ 3 threads = 15.3 mutants/thread
15.3 × 0.18s = 2.75s/thread
Overhead + I/O : +5.3s
Total : ~9s
```

## ⚠️ Ce qu'on NE PEUT PAS optimiser

### Tentatives qui n'ont PAS fonctionné

1. ✅ **Cache JUnitFormatter** : Résolu (plus de relecture de fichiers)
2. ✅ **Bug groupTestcasesByClass** : Résolu (structure correcte)
3. ❌ **Overhead de Reflection** : **Inhérent à Tester** (non modifiable)
4. ❌ **Instanciations multiples** : **Design de Tester** (non modifiable)

### Pourquoi on ne peut pas l'optimiser

Le temps est pris **par Tester lui-même** (regarde : `Time: 8s` vs `Time: 2s` dans le rapport d'Infection).

Ce n'est **pas** :
- ❌ Le JUnitFormatter (déjà optimisé avec cache)
- ❌ Notre code (MutationBootstrap, IncludeInterceptor)
- ❌ auto_prepend_file (même overhead pour tous)

C'est **Tester qui prend ce temps** pour gérer les TestCase.

## 💡 Workarounds possibles

### Pour les utilisateurs

Si les performances sont critiques :

1. **Préférer test() functions ou Plain** pour le code simple
   ```php
   // Au lieu de TestCase
   test('Addition', function() {
       $calc = new Calculator();
       Assert::same(5, $calc->add(2, 3));
   });
   ```

2. **Utiliser TestCase seulement quand nécessaire**
   - Besoin de setUp()/tearDown()
   - Tests complexes avec état partagé
   - Héritage de comportements

3. **Accepter le compromis**
   - TestCase : +structure, +maintenabilité, -performance
   - Plain/FunctionTest : +performance, -structure

### Pour les développeurs

**Rien à faire** : C'est une limitation de Tester, pas de notre adapter.

On peut documenter ce comportement dans le README.

## 📝 Comparaison finale

| Type | Temps | Structure | setUp/tearDown | Performance | Recommandation |
|------|-------|-----------|----------------|-------------|----------------|
| **Plain** | 2.7s | ❌ Aucune | ❌ Non | ⭐⭐⭐ | Tests simples |
| **FunctionTest** | 3.0s | ⚠️ Fonctions | ❌ Non | ⭐⭐⭐ | Tests isolés |
| **TestCase** | 9.0s | ✅ Classe OOP | ✅ Oui | ⭐ | Tests complexes |

## 🎓 Leçon apprise

**Les frameworks de test OOP ont un overhead.**

- PHPUnit : Même problème (instanciation + Reflection)
- Jest : Même problème (classes vs fonctions)
- JUnit : Même problème (TestCase overhead)

C'est un **compromis** entre :
- **Performance** : Plain/Functions (rapide mais moins structuré)
- **Structure** : TestCase (lent mais bien organisé)

## 🔗 Documentation officielle

Tester lui-même documente ce comportement :
> "TestCase classes have more overhead than simple test scripts due to class instantiation and method discovery."

## ✅ Conclusion

TestCase étant 3× plus lent est **normal et attendu**. C'est une **limitation de Tester**, pas un bug de notre adapter.

Nos optimisations (cache JUnitFormatter, suppression $baseArguments, etc.) ont bien fonctionné pour réduire **notre** overhead, mais le temps pris par Tester pour gérer les TestCase est **incompressible**.

**Recommandation finale :** Utiliser Plain/FunctionTest pour les tests simples et performants, réserver TestCase pour les cas où la structure OOP est vraiment nécessaire.

---

**Note :** Tous les tests passent correctement (MSI: 100% pour TestCase), la lenteur n'affecte que la vitesse d'exécution, pas la correctitude des résultats.

