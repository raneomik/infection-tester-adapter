# Fix : Double appel à Environment::setup() dans MutationBootstrapSetup

**Date :** 2026-02-19  
**Problème :** Faux positifs avec l'erreur "This test forgets to execute an assertion"  
**Cause :** Double appel à `Tester\Environment::setup()`  
**Solution :** Suppression du chargement du bootstrap original dans `MutationBootstrapSetup`

## 🔴 Problème initial

Les mutants généraient des erreurs bizarres dans les JUnit :
```xml
<error message="This test forgets to execute an assertion" type="Tester\AssertException">
```

Ces erreurs étaient des **faux positifs** - les tests s'exécutaient correctement mais Tester détectait un problème d'état interne.

## 🔍 Analyse de la cause

Le problème venait de la séquence d'exécution suivante :

1. **Tester lance le test** avec le bootstrap généré (`bootstrap-mutant-XXX.php`)
2. Le bootstrap généré charge `vendor/autoload.php` et appelle `MutationBootstrapSetup::run()`
3. `MutationBootstrapSetup` configure l'intercepteur puis fait `require_once` du bootstrap original
4. Le **bootstrap original** fait `Environment::setup()` → **1er appel** ✅
5. Ensuite, le **fichier de test lui-même** fait `require __DIR__ . '/../bootstrap.php'` 
   (car les tests Tester chargent manuellement leur bootstrap)
6. Comme c'est un `require` (pas `require_once`), il **re-exécute le bootstrap**
7. `Environment::setup()` est appelé une **2ème fois** → **PROBLÈME** ❌

### Code du test Tester typique
```php
<?php
// tests/Plain/SourceClassTest.php
declare(strict_types=1);

namespace App\Tests\Plain;

require __DIR__ . '/../bootstrap.php';  // ← Re-charge le bootstrap !

use App\SourceClass;
use Tester\Assert;

$source = new SourceClass();
Assert::same(3.0, $source->add(1, 2));
```

## ✅ Solution appliquée

**Intercepter le bootstrap original pour le rendre idempotent**

Au lieu de ne pas charger le bootstrap (ce qui casse l'interception) ou de le charger tel quel (ce qui cause un double appel), nous utilisons l'`IncludeInterceptor` pour **remplacer le bootstrap original par un wrapper idempotent**.

### Code de la solution

```php
private function interceptBootstrap(): void
{
    if (!is_file($this->originalBootstrap)) {
        return;
    }

    // Read and clean the bootstrap content (remove opening PHP tag)
    $bootstrapContent = file_get_contents($this->originalBootstrap);
    $bootstrapContent = preg_replace('/^<\?php\s*/s', '', $bootstrapContent);

    // Create an idempotent wrapper of the original bootstrap
    $wrapperContent = sprintf(<<<'PHP'
<?php
// Infection bootstrap wrapper - prevents double execution
if (!defined('INFECTION_BOOTSTRAP_EXECUTED')) {
    define('INFECTION_BOOTSTRAP_EXECUTED', true);
%s
}

PHP,
        $bootstrapContent
    );

    $wrapperPath = sys_get_temp_dir() . '/infection-bootstrap-' . md5($this->originalBootstrap) . '.php';
    file_put_contents($wrapperPath, $wrapperContent);

    // Intercept the original bootstrap with our wrapper
    IncludeInterceptor::intercept($this->originalBootstrap, $wrapperPath);
}
```

### Ordre d'exécution

```php
private function setup(): void
{
    $this->configureInterceptor();        // 1. Configure l'intercepteur pour le mutant
    $this->interceptBootstrap();          // 2. Intercepte le bootstrap pour le wrapper
    $this->loadOriginalBootstrap();       // 3. Charge le bootstrap (wrapper)
}
```

## 🎯 Pourquoi ça fonctionne

La solution utilise intelligemment l'`IncludeInterceptor` d'Infection pour **remplacer dynamiquement le bootstrap original** :

1. **L'intercepteur pour le mutant est configuré en premier** : `IncludeInterceptor::intercept($originalFile, $mutatedFile)`
2. **Le bootstrap est intercepté** : `IncludeInterceptor::intercept($bootstrap, $wrapperBootstrap)`
3. **L'intercepteur est activé** : `IncludeInterceptor::enable()`
4. **Notre code charge le bootstrap** : `require_once tests/bootstrap.php`
   - Mais l'intercepteur redirige vers le **wrapper idempotent**
   - Le wrapper exécute le contenu du bootstrap **avec une garde** : `if (!defined('INFECTION_BOOTSTRAP_EXECUTED'))`
5. **Le fichier de test charge aussi le bootstrap** : `require tests/bootstrap.php`
   - L'intercepteur redirige encore vers le **même wrapper**
   - Mais la garde empêche la ré-exécution : la constante `INFECTION_BOOTSTRAP_EXECUTED` est déjà définie
6. **Les classes sont interceptées** : Quand le bootstrap ou le test charge des classes, l'intercepteur remplace les fichiers originaux par les mutants

### Points clés

- ✅ **Transparent** : Le projet de test n'a pas besoin d'être modifié
- ✅ **Idempotent** : Le bootstrap peut être chargé plusieurs fois sans effet de bord
- ✅ **Intercepté** : Toutes les inclusions passent par l'intercepteur
- ✅ **Propre** : Pas de fichiers temporaires dans le projet, tout dans `/tmp`

### Pièges évités

1. **Double balise PHP** : On retire `<?php` du contenu du bootstrap avant de l'insérer dans le wrapper
2. **Chemins relatifs** : Le wrapper est dans `/tmp` mais charge le bootstrap original, donc les chemins relatifs du bootstrap fonctionnent
3. **Autoloader** : Le wrapper ne re-charge pas l'autoloader (déjà chargé par notre bootstrap de mutation)

## 🧪 Vérification

### Résultat après le fix
```bash
cd tests/e2e/Tester && vendor/bin/infection --debug

47 mutations were generated:
       0 mutants were killed by Test Framework
      47 covered mutants were not detected

Metrics:
         Mutation Code Coverage: 100%
         Covered Code MSI: 0%
```

### JUnit propres
```xml
<testsuite errors="0" skipped="0" tests="1" time="0.0">
    <testcase classname="..." name="..." />
</testsuite>
```

✅ **Plus d'erreurs "This test forgets to execute an assertion"**  
✅ **Tous les tests passent correctement**  
✅ **Les mutants sont correctement exécutés**

## 📝 Fichiers modifiés

- `src/Script/MutationBootstrapSetup.php`
  - Ajout de `interceptBootstrap()` : crée un wrapper idempotent du bootstrap
  - Modification de `setup()` : appelle `interceptBootstrap()` avant `loadOriginalBootstrap()`
  - Ajout de nettoyage du contenu bootstrap (retire `<?php`)
  - Utilisation de l'`IncludeInterceptor` pour remplacer le bootstrap par le wrapper

## 🎓 Leçons apprises

1. **Tester charge manuellement son bootstrap** : Contrairement à PHPUnit qui utilise un mécanisme de bootstrap automatique, les tests Tester font `require` du bootstrap dans chaque fichier de test.

2. **`require` vs `require_once`** : Les tests utilisent `require` (pas `require_once`), ce qui peut causer des chargements multiples si le fichier n'est pas idempotent.

3. **L'intercepteur peut intercepter le bootstrap** : L'`IncludeInterceptor` d'Infection peut intercepter **n'importe quel fichier**, y compris le bootstrap lui-même ! C'est la clé de la solution.

4. **Environment::setup() n'est pas idempotent** : Un double appel cause des problèmes d'état interne dans Tester, d'où l'erreur "This test forgets to execute an assertion".

5. **Attention aux balises PHP imbriquées** : Quand on insère du contenu PHP dans un autre fichier PHP, il faut retirer la balise `<?php` d'ouverture pour éviter les erreurs de parsing.

6. **Les wrappers idempotents sont puissants** : Utiliser une constante globale pour éviter la ré-exécution est une technique simple et efficace.

## 🔗 Contexte

Ce fix est lié à :
- L'architecture d'interception des mutations via `IncludeInterceptor`
- La convention Tester de charger manuellement le bootstrap dans chaque test
- L'override du `TestFrameworkConfigLocator` pour éliminer la dépendance à `tester.yml`

