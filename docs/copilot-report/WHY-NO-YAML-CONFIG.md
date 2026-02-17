# Pourquoi Tester n'a PAS besoin de fichier de configuration

## TL;DR

**Tester suit la philosophie "Convention over Configuration"** de Nette - Il n'a PAS besoin de `tester.yml` ou autre config !

## Comparaison avec d'autres frameworks

### PHPUnit (besoin de config)

```xml
<!-- phpunit.xml - NÉCESSAIRE -->
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory>src</directory>
        </whitelist>
    </filter>
</phpunit>
```

**Pourquoi ?** PHPUnit a besoin de savoir :
- Où chercher les tests
- Comment les grouper
- Quoi inclure dans la coverage
- Quel bootstrap utiliser

### Codeception (besoin de config)

```yaml
# codeception.yml - NÉCESSAIRE
actor: Tester
paths:
    tests: tests
    output: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
settings:
    bootstrap: _bootstrap.php
modules:
    config:
        Db:
            dsn: 'mysql:host=localhost;dbname=test'
```

**Pourquoi ?** Codeception a besoin de :
- Configurer les modules
- Définir les chemins personnalisés
- Setup des environnements

### Tester (PAS besoin de config) ✨

```bash
# C'est tout ce qu'il faut !
vendor/bin/tester tests/
```

**Pourquoi ça marche ?** Tester utilise des **conventions** :

| Besoin | Convention Tester | Config PHPUnit |
|--------|-------------------|----------------|
| **Tests directory** | `tests/` (automatique) | `<directory>tests</directory>` |
| **Bootstrap** | `tests/bootstrap.php` (automatique) | `bootstrap="tests/bootstrap.php"` |
| **Test files** | `*Test.php` ou `*.phpt` (pattern) | `<testsuite>` explicit |
| **Source files** | Pas besoin (coverage via autoload) | `<whitelist>` explicit |
| **Output** | `-o junit`, `-o tap` (CLI) | XML config |

## Dans cet adapter

### Ce qui est hard-codé (conventions)

```php
// CommandScriptBuilder.php
private const TESTS_DIR = 'tests';
private const BOOTSTRAP = 'tests/bootstrap.php';

// TesterAdapterFactory.php
$testFrameworkExecutable = 'vendor/bin/tester';
```

### Ce qui est auto-détecté

```php
// CoverageDriverDetector.php
- PCOV (si extension_loaded('pcov') && pcov.enabled)
- Xdebug (si extension_loaded('xdebug') && xdebug.mode=coverage)
- PHPDBG (si PHP_SAPI === 'phpdbg')
```

### Ce qui vient d'Infection

```php
// infection.json5
{
    "source": {
        "directories": ["src"]  // ← Utilisé pour la coverage
    }
}
```

## Le fichier tester.yml dans e2e

```yaml
# tests/e2e/Tester/tester.yml
# Tester configuration file (minimal)
# Note: TesterAdapter uses conventions (tests/ and tests/bootstrap.php)
# This file exists to satisfy Infection's file existence check
```

Ce fichier est **vide** et existe uniquement parce que certaines versions anciennes d'Infection vérifiaient l'existence d'un fichier de config du framework.

**Avec Infection moderne, même ce fichier n'est plus nécessaire !**

## Avantages de cette approche

✅ **Simplicité** - Pas de config à maintenir
✅ **Zéro friction** - Fonctionne out-of-the-box
✅ **Moins d'erreurs** - Pas de config mal formattée
✅ **Portabilité** - Même structure partout
✅ **Rapidité** - Pas de parsing YAML/XML

## Cas où on POURRAIT avoir besoin de config

### ❌ Chemins non-standard ?
```
NON - Tester s'attend à tests/ (standard PSR)
```

### ❌ Bootstrap personnalisé ?
```
NON - tests/bootstrap.php est le standard
```

### ❌ Plusieurs suites de tests ?
```
NON - Tester exécute tous les tests trouvés
Infection filtre par mutation, pas par suite
```

### ❌ Options Tester spécifiques ?
```
NON - Passées via Infection extraOptions si besoin
```

## Conclusion

**Le support YAML/XML pour Tester serait de la sur-ingénierie.**

Tester fonctionne parfaitement avec :
1. Conventions de chemins
2. Auto-détection du coverage driver
3. Configuration Infection standard

**Recommandation** : Ne pas ajouter de parsing de config - C'est contre la philosophie de Tester et n'apporte aucune valeur.

## Si un utilisateur veut vraiment personnaliser

Il peut utiliser les options d'Infection :

```json5
// infection.json5
{
    "testFramework": "tester",
    "testFrameworkOptions": "--php-ini pcov.enabled=1 --setup custom-setup.php",
    "source": {
        "directories": ["src"]
    }
}
```

Tout passe par Infection, pas besoin de config Tester séparée ! 🎯

