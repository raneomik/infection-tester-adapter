# ✅ Solution Heredoc Lisible - sprintf + heredoc sans interpolation

## 🎯 Objectif

Garder les **heredoc lisibles** tout en évitant les bugs d'interpolation PHP.

## ❌ Problème initial (heredoc avec interpolation)

```php
// BUG - L'interpolation peut mal gérer le <?php initial
return <<<PHP
<?php
\$autoloadPath = {$autoloadLiteral};  // Interpolation
...
PHP;
```

**Résultat** : Le `<?php` n'était pas généré correctement dans certains cas.

## ✅ Solution : sprintf + heredoc sans interpolation

```php
return sprintf(
    <<<'PHP'
<?php
/**
 * Tester job prepend script
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

## 🚀 Avantages

✅ **Lisible** - Le heredoc est clair et facile à lire
✅ **Maintenable** - Code PHP visible sans échappement
✅ **Fiable** - `<<<'PHP'` sans interpolation + sprintf pour les valeurs
✅ **<?php garanti** - Le tag PHP est toujours présent

## 🔑 La clé : `<<<'PHP'` avec quotes

```php
<<<'PHP'    // ← Notez les quotes ' autour de PHP
```

Cela désactive l'interpolation PHP dans le heredoc, comme avec les single quotes.

## 📁 Fichiers modifiés

1. **`src/Coverage/Script/Template/PrependScriptTemplate.php`**
2. **`src/Coverage/Script/Template/SetupScriptTemplate.php`**

Les deux utilisent maintenant `sprintf(<<<'PHP' ... PHP, ...)` pour :
- Garder la lisibilité du heredoc
- Éviter les bugs d'interpolation
- Garantir que le `<?php` est toujours généré

## 🧪 Test

```bash
cd tests/e2e/Tester
rm -rf vendor/raneomik && composer install
vendor/bin/infection --test-framework=tester

# ✅ 45 mutations generated
# ✅ 100% Mutation Code Coverage
```

## 📝 Pattern recommandé pour les templates

```php
public static function generate(/*...*/): string
{
    // 1. Préparer les variables
    $var1 = var_export($value1, true);
    $var2 = var_export($value2, true);

    // 2. Utiliser sprintf + heredoc sans interpolation
    return sprintf(
        <<<'PHP'
<?php
// Template lisible
$variable1 = %s;
$variable2 = %s;
// ... code ...

PHP,
        $var1,
        $var2
    );
}
```

## 🎉 Résultat

**Le meilleur des deux mondes** :
- ✅ Heredoc lisible (comme avant)
- ✅ Pas de bug d'interpolation
- ✅ Code généré correct

**Plus besoin de concatenation !** Le heredoc reste lisible et fonctionne parfaitement.

