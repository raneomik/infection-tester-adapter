# Procédure de test pour les tests procéduraux/function

## Modifications apportées

### 1. CoverageRuntime.php - extractMethodFromArgv()
✅ Corrigé pour parcourir tous les arguments argv avec `foreach`

### 2. JUnitFormatter.php - parseTesterFormat()
✅ **FIX PRINCIPAL** : Gère maintenant les tests sans `method=` (procéduraux et test())
- Pattern 1: `"/path/Test.php method=testMethod"` → TestCase
- Pattern 2: `"/path/Test.php"` → Procédural/test() → utilise `method='test'`

### 3. Tous les tests ont des namespaces
✅ FunctionTest et Plain ont tous des namespaces maintenant

## Comment tester

```bash
cd tests/e2e/Tester

# 1. Nettoyer
rm -rf var/infection

# 2. Lancer Infection
../../../vendor/bin/infection --test-framework=tester --threads=1 --min-msi=0

# 3. Vérifier que les tests sont trouvés
cat var/infection/infection/junit.xml | grep -E "FunctionTest|Plain"

# 4. Vérifier qu'il n'y a plus d'erreur "For FQCN:"
# L'erreur devrait avoir disparu
```

## Ce qui devrait se passer maintenant

### Pour un TestCase (ex: TestCase/SourceClassTest.php)
```
Tester génère: "/path/SourceClassTest.php method=testAddition"
JUnitFormatter parse: class=SourceClassTest, method=testAddition
Coverage génère: App\Tests\TestCase\SourceClassTest::testAddition
✅ Match !
```

### Pour un test procédural (ex: Plain/SourceClassTest.php)
```
Tester génère: "/path/SourceClassTest.php" (PAS de method=)
JUnitFormatter parse: class=SourceClassTest, method=test (synthétique)
Coverage génère: App\Tests\Plain\SourceClassTest::test
✅ Match !
```

### Pour un test() function (ex: FunctionTest/SourceClassTest.php)
```
Tester génère: "/path/SourceClassTest.php" (PAS de method=)
JUnitFormatter parse: class=SourceClassTest, method=test (synthétique)
Coverage génère: App\Tests\FunctionTest\SourceClassTest::test
✅ Match !
```

## Résultat attendu

L'erreur `For FQCN: App\Tests\FunctionTest\SourceClassTest` devrait **disparaître** car :
1. Le JUnitFormatter parse maintenant les tests sans `method=`
2. Il crée `App\Tests\FunctionTest\SourceClassTest::test` dans le JUnit
3. Le CoverageRuntime crée le même ID dans les fragments de coverage
4. Infection trouve le test dans le JUnit → ✅ Success !

## Debug si ça ne marche pas

1. Vérifier le JUnit brut **AVANT** normalisation :
```bash
# Lancer juste les tests avec Tester directement
# Le JUnit de Tester sera généré quelque part dans var/
```

2. Vérifier le JUnit **APRÈS** normalisation :
```bash
cat var/infection/infection/junit.xml
# Doit contenir des testsuite avec name="App\Tests\FunctionTest\SourceClassTest"
```

3. Vérifier les fragments de coverage :
```bash
ls -la var/infection/coverage-fragments/
# Doit contenir des fichiers .phpser
```

4. Activer le mode debug :
```bash
../../../vendor/bin/infection --test-framework=tester --debug
# Garde les fichiers temporaires pour analyse
```

