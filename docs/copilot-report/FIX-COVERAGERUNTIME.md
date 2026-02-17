# ✅ FIX RÉEL - CoverageRuntime not found

## 🔴 Le VRAI problème

La classe `CoverageRuntime` n'était pas trouvée lors de l'exécution des tests :

```
PHP Fatal error: Class "Raneomik\InfectionTestFramework\Tester\Coverage\CoverageRuntime" not found
in tester_job_prepend.php:19
```

## 🐛 La cause

Le **heredoc PHP dans les templates** ne générait pas correctement le `<?php` initial !

```php
// AVANT - BUG
return <<<PHP
<?php
... code ...
PHP;

// Résultat généré : MANQUE le <?php !
\Raneomik\InfectionTestFramework\Tester\Script\CoverageRuntime::start(...)
```

Le fichier généré commençait **directement par le code** sans balise `<?php`, donc :
1. PHP ne l'exécutait pas comme du code PHP
2. L'autoload n'était jamais chargé
3. `CoverageRuntime` était introuvable

## ✅ La solution

**Utiliser sprintf + heredoc SANS interpolation** (`<<<'PHP'`) :

```php
// APRÈS - OK ET LISIBLE !
return sprintf(
    <<<'PHP'
<?php
/**
 * Generated script
 */

declare(strict_types=1);

$autoloadPath = %s;
if (is_string($autoloadPath) && $autoloadPath !== '' && is_file($autoloadPath)) {
    require_once $autoloadPath;
}

\Raneomik\InfectionTestFramework\Tester\Coverage\CoverageRuntime::start(
    %s,
    %s
);

PHP,
    $autoloadLiteral,
    $fragmentLiteral,
    $srcDirsLiteral
);
```

**La clé** : `<<<'PHP'` (avec quotes) désactive l'interpolation + `sprintf()` pour injecter les valeurs.

## 📁 Fichiers corrigés

1. **`src/Coverage/Script/Template/PrependScriptTemplate.php`**
   - Remplacé heredoc par concatenation
   - Garantit que `<?php` est bien au début
   - L'autoload est chargé avant d'appeler `CoverageRuntime`

2. **`src/Coverage/Script/Template/SetupScriptTemplate.php`**
   - Même correction pour cohérence

## 🧪 Test

```bash
cd tests/e2e/Tester

# Important: supprimer vendor/raneomik pour forcer la recopie
rm -rf vendor/raneomik
composer install

# Test
rm -rf var/infection
vendor/bin/infection --test-framework=tester
```

**Résultat** :
```
45 mutations were generated:
      45 mutants were killed by Test Framework

Metrics:
         Mutation Code Coverage: 100%
         Covered Code MSI: 100%
```

## ⚠️ Note sur "symlink": false

Avec `"symlink": false`, Composer **copie** les fichiers au lieu de faire un symlink.

**Conséquence** : Après modification du parent, il faut :
```bash
rm -rf vendor/raneomik
composer install
```

Simple `composer update` ne suffit pas car les fichiers sont copiés, pas liés.

## 🎯 Leçon apprise

**Les heredoc PHP avec interpolation peuvent avoir des comportements inattendus.**

Pour des templates générant du code PHP, la meilleure solution :

```php
// ✅ sprintf + heredoc SANS interpolation
sprintf(<<<'PHP' ... PHP, $var1, $var2)
```

**Avantages** :
- ✅ Lisible comme un heredoc classique
- ✅ Pas de bug d'interpolation (`<<<'PHP'` désactive l'interpolation)
- ✅ Le `<?php` est toujours présent
- ✅ Maintenable

**Alternatives moins bonnes** :
- ❌ Heredoc avec interpolation `<<<PHP` - Bugs subtils
- ⚠️ Concatenation `"<?php\n" . ...` - Peu lisible mais fonctionne

## 🎉 Résultat

✅ **CoverageRuntime trouvé et chargé**
✅ **45 mutations générées et testées**
✅ **100% Mutation Code Coverage**
✅ **Infection fonctionne parfaitement !**

