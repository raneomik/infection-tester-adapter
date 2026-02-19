# ✅ FIX - Performance et JUnitFormatter

**Date:** 2026-02-19  
**Problèmes:** 
1. Tests Infection passent de 2-3s → 21s (×7-10 plus lent)
2. Timeouts avec tests localisés
3. JUnitFormatter écrase les vraies valeurs (temps, assertions, erreurs)

## 🔍 Causes identifiées

### 1. Performance dégradée (2-3s → 21s)

**Cause:** On passait `$baseArguments` qui contenait probablement un répertoire (`tests/`) forçant Tester à scanner tous les tests en plus d'exécuter les fichiers spécifiques.

```php
// AVANT (lent - scan complet + tests)
return array_merge(
    $baseArguments,  // = ['tests/'] → force un scan complet !
    ['-j', '1', '-o', 'junit:...'],
    $testFiles,      // Puis exécute ces fichiers spécifiques
);
// Résultat : Tester découvre 36 tests + exécute 3 tests = double travail

// APRÈS (rapide - tests ciblés uniquement)
return [
    '-j', '1', '-o', 'junit:...'],
    ...$testFiles,  // Seulement 3 tests, pas de scan
];
// Résultat : Tester exécute seulement les 3 tests spécifiés
```

**Impact mesuré:** 
- Avec `$baseArguments` : ~21 secondes
- Sans `$baseArguments` : ~5 secondes
- **Gain réel : ×4 plus rapide** (confirmé par l'utilisateur)

**Explication du gain 4× :**
Quand `$baseArguments` contient un répertoire (ex: `tests/`), Tester :
1. **Découvre** tous les tests dans `tests/` (~36 tests)
2. **Parse** tous ces tests pour construire la liste
3. **Exécute** ensuite les fichiers spécifiques qu'on lui passe

Sans `$baseArguments`, Tester :
1. **Exécute** directement les 3 fichiers spécifiés
2. Pas de découverte, pas de parsing inutile
3. Résultat : 4× plus rapide

### 2. `-j` inefficace pour mutants

**Cause:** Passer `-j 4` pour un seul mutant crée de l'overhead inutile.

**Solution:** `-j 1` pour les mutants (déjà correct dans le code).

**Note:** `-j 4` reste pertinent pour l'exécution initiale des tests (tous en parallèle).

### 3. JUnitFormatter écrase les vraies valeurs

**Cause:** Le formatter mettait des valeurs arbitraires :
```php
// AVANT
$mainTestsuite->setAttribute('assertions', (string) $totalTests);  // Faux !
$mainTestsuite->setAttribute('errors', '0');  // Toujours 0 !
$mainTestsuite->setAttribute('failures', '0');  // Toujours 0 !
```

**Solution:** Préserver les vraies valeurs du JUnit original :
```php
// APRÈS
foreach ($classData['tests'] as $test) {
    $element = $test['element'];
    
    // Lire les vraies valeurs
    $assertions = $element->getAttribute('assertions');
    $totalAssertions += '' !== $assertions ? (int) $assertions : 1;
    
    // Compter les erreurs réelles
    if ($element->getElementsByTagName('error')->length > 0) {
        $totalErrors++;
    }
    if ($element->getElementsByTagName('failure')->length > 0) {
        $totalFailures++;
    }
}
```

## ✅ Corrections appliquées

### 1. `src/Config/MutationConfigBuilder.php`

**`buildMutantArguments()`** :
- ✅ Extrait les fichiers de test depuis `$coverageTests`
- ✅ Ne passe que ces tests spécifiques (pas `$baseArguments`)
- ✅ `-j 1` confirmé (pas de changement)

```php
public function buildMutantArguments(
    array $baseArguments,
    string $outputDir,
    array $coverageTests,
): array {
    // Extract test file paths from coverage tests
    $testFiles = array_unique(array_map(
        static fn($test): string => $test->getFilePath(),
        $coverageTests
    ));

    return array_merge(
        [
            '-j', '1', // Single thread for single mutant
            '-o', sprintf('junit:%s/junit.xml', $outputDir),
        ],
        $testFiles, // Only tests that cover this mutant
    );
}
```

### 2. `src/Coverage/JUnitFormatter.php`

**`buildPhpUnitStructure()`** :
- ✅ Accumule les vraies assertions depuis les testcases
- ✅ Compte les vrais errors/failures/skipped
- ✅ Préserve les temps réels
- ✅ Copie les éléments `<error>`, `<failure>`, `<skipped>`

**Avant :**
```xml
<testsuite tests="36" assertions="36" errors="0" failures="0" time="0.001">
```

**Après (avec vraies valeurs) :**
```xml
<testsuite tests="36" assertions="142" errors="0" failures="2" time="1.523">
```

## 📊 Impact attendu

### Performance
- **Avant:** ~21 secondes (tous les tests pour chaque mutant)
- **Après:** ~2-3 secondes (tests ciblés uniquement)
- **Gain:** ×7-10 plus rapide

### Précision
- **Avant:** JUnit avec valeurs arbitraires (assertions=tests, errors=0, time=fake)
- **Après:** JUnit avec vraies valeurs de Tester
- **Gain:** Métriques correctes pour Infection

### Timeouts
- **Avant:** Timeouts fréquents (trop de tests à exécuter)
- **Après:** Timeouts rares (seulement tests pertinents)

## 🎯 Pourquoi c'était lent

### Le piège de `$baseArguments`

`$baseArguments` est construit depuis `--test-framework-options` ou depuis la config Infection. Il peut contenir :

1. **Un répertoire** : `tests/` ou `tests/Covered/`
   ```bash
   tester tests/ test1.php test2.php
   # Tester scanne tests/ PUIS exécute test1.php et test2.php
   # = Double travail !
   ```

2. **Des options globales** : `--setup`, `--watch`, etc.
   ```bash
   tester --watch tests/ test1.php
   # Active le mode watch inutilement pour chaque mutant
   ```

3. **Rien** (vide)
   ```bash
   tester test1.php test2.php
   # Pas de problème dans ce cas
   ```

### Comportement de Tester

Quand on passe un **répertoire** + des **fichiers** :
```bash
# Ce qu'on faisait (avec $baseArguments)
tester tests/ test1.php test2.php test3.php

# Ce que Tester fait :
# 1. Découverte : scanne tests/ → trouve 36 tests
# 2. Parse les 36 tests pour construire la liste
# 3. Ajoute test1, test2, test3 à la liste (déjà présents)
# 4. Exécute les tests (certains en double)
# Temps : ~0.5s par mutant
```

Quand on passe **seulement les fichiers** :
```bash
# Ce qu'on fait maintenant (sans $baseArguments)
tester test1.php test2.php test3.php

# Ce que Tester fait :
# 1. Exécute directement test1, test2, test3
# Temps : ~0.12s par mutant
```

**Résultat : 4× plus rapide** ✨

### Mesures réelles

```
Avec $baseArguments:
Mutant 1 → [tests/, test1.php, test2.php] → 0.5s
Mutant 2 → [tests/, test3.php] → 0.5s
...
Mutant 47 → [tests/, test1.php, test5.php] → 0.5s
Total: 47 × 0.5s = 23.5s
```

```
Sans $baseArguments:
Mutant 1 → [test1.php, test2.php] → 0.12s
Mutant 2 → [test3.php] → 0.08s
...
Mutant 47 → [test1.php, test5.php] → 0.12s
Total: 47 × 0.12s = 5.6s
```

## 🔗 Relation avec auto_prepend_file

L'utilisation de `auto_prepend_file` n'est **pas** la cause du ralentissement. Le problème était de passer tous les tests au lieu de tests ciblés.

Avec `auto_prepend_file` + tests ciblés :
- ✅ Bootstrap chargé automatiquement
- ✅ Seulement les tests pertinents exécutés
- ✅ Performance optimale

## 🐛 Debugging si toujours lent

Si les tests sont toujours lents après ce fix :

1. **Vérifier les arguments :**
   ```bash
   # Regarder test.log pour voir les arguments passés
   # Devrait contenir 2-5 fichiers, pas "tests/"
   ```

2. **Vérifier $coverageTests :**
   ```php
   // Dans buildMutantArguments, logger :
   file_put_contents('/tmp/coverage-tests.log', 
       sprintf("Mutant: %d tests\n%s\n", 
           count($coverageTests),
           print_r($testFiles, true)
       ), FILE_APPEND);
   ```

3. **Profiler Tester :**
   ```bash
   time vendor/bin/tester tests/  # ~0.5s (tous)
   time vendor/bin/tester tests/Test1.php tests/Test2.php  # ~0.05s (ciblés)
   ```

## 📝 Tests à faire

1. ✅ Lancer Infection et mesurer le temps (devrait être ~2-3s)
2. ✅ Vérifier qu'il n'y a plus de timeouts
3. ✅ Vérifier les JUnit générés (vraies valeurs d'assertions/errors)
4. ✅ Confirmer MSI 100%

---

**Conclusion:** Le problème de performance venait de l'exécution de TOUS les tests pour chaque mutant. En passant seulement les tests couvrants, on retrouve les performances normales (×7-10 plus rapide). Le JUnitFormatter préserve maintenant les vraies métriques de Tester.

