# ✅ Solution PSR-4 Override : SUCCÈS !

## Date : 2026-02-18

## Question

> "Peut-on passer par l'override de ce TestConfigLocator via composer > psr4 ?"

## Réponse

**OUI ! C'EST POSSIBLE ET ÇA MARCHE ! 🎉**

## Solution implémentée

### 1. Créer un override de la classe Infection

**Fichier** : `src/Override/Infection/TestFramework/Config/TestFrameworkConfigLocator.php`

Cette classe:
- Garde le comportement original pour PHPUnit et autres frameworks
- Ajoute une logique spéciale pour Tester (pas d'exception si pas de fichier config)
- Retourne des fallbacks intelligents pour Tester

### 2. Ajouter le mapping PSR-4 dans composer.json

```json
{
  "autoload": {
    "psr-4": {
      "Infection\\TestFramework\\Config\\": "src/Override/Infection/TestFramework/Config/",
      "Raneomik\\InfectionTestFramework\\Tester\\": "src/"
    }
  }
}
```

**L'ordre est important !** Le namespace `Infection\TestFramework\Config\` est mappé **AVANT** le mapping global d'Infection dans l'autoloader Composer.

### 3. Régénérer l'autoloader

```bash
composer dump-autoload
```

## Comment ça fonctionne

### Mécanisme PSR-4 de Composer

Quand Composer génère l'autoloader, il crée un tableau avec les mappings PSR-4 :

```php
// vendor/composer/autoload_psr4.php
return array(
    'Infection\\TestFramework\\Config\\' => array($baseDir . '/src/Override/Infection/TestFramework/Config'),
    'Infection\\' => array($vendorDir . '/infection/infection/src'),
    // ...
);
```

**Le premier mapping trouvé gagne !** Donc notre classe est chargée **AVANT** celle d'Infection.

### Vérification

```bash
php -r "
require 'vendor/autoload.php';
\$rc = new ReflectionClass('Infection\TestFramework\Config\TestFrameworkConfigLocator');
var_dump(\$rc->getFileName());
"
```

**Résultat** :
```
string(120) "/home/marek/Projects/infection-tester-adapter/src/Override/Infection/TestFramework/Config/TestFrameworkConfigLocator.php"
```

✅ **Notre classe est bien chargée !**

## Comportement de l'override

### Pour Tester (`$cliTool === 'tester'`)

1. Cherche d'abord les fichiers de config classiques (tester.yml, tester.xml, etc.)
2. Si aucun trouvé, **ne lève pas d'exception** mais retourne un fallback :
   - `tests/bootstrap.php` (si existe)
   - `composer.json` (si existe)
   - Répertoire `tests/` (si existe)
   - Répertoire du projet

### Pour les autres frameworks (PHPUnit, etc.)

Comportement **inchangé** : lève une exception si pas de fichier de configuration trouvé.

## Tests

### Test 1 : Tester sans fichier tester.yml

```bash
cd tests/e2e/Tester
rm -f tester.yml
php -r "
require '../../../vendor/autoload.php';
\$locator = new \Infection\TestFramework\Config\TestFrameworkConfigLocator(getcwd());
\$path = \$locator->locate('tester');
echo \$path . PHP_EOL;
"
```

**Résultat** :
```
/home/marek/Projects/infection-tester-adapter/tests/e2e/Tester/tests/bootstrap.php
```

✅ **Fonctionne sans fichier tester.yml !**

### Test 2 : PHPUnit avec phpunit.xml.dist

```bash
cd /home/marek/Projects/infection-tester-adapter
php -r "
require 'vendor/autoload.php';
\$locator = new \Infection\TestFramework\Config\TestFrameworkConfigLocator(getcwd());
\$path = \$locator->locate('phpunit');
echo \$path . PHP_EOL;
"
```

**Résultat** :
```
/home/marek/Projects/infection-tester-adapter/phpunit.xml.dist
```

✅ **PHPUnit fonctionne normalement !**

## Avantages de cette solution

✅ **Pas de fichier tester.yml requis** - Vraie "convention over configuration"
✅ **Transparent** - Fonctionne automatiquement sans configuration utilisateur
✅ **Compatible** - N'affecte pas PHPUnit ni autres frameworks
✅ **Maintenable** - Un seul fichier à maintenir
✅ **Élégant** - Utilise les mécanismes standards de Composer

## Inconvénients / Limitations

⚠️ **Override global** : Affecte tous les projets qui utilisent cet adapter
⚠️ **Ordre PSR-4** : Doit être déclaré **avant** Infection dans composer.json
⚠️ **Maintenance** : Doit rester compatible avec les évolutions d'Infection

## Fichiers créés

1. ✅ **src/Override/Infection/TestFramework/Config/TestFrameworkConfigLocator.php**
   - Override de la classe d'Infection
   - Logique spéciale pour Tester
   
2. ✅ **composer.json modifié**
   - Mapping PSR-4 ajouté pour `Infection\TestFramework\Config\`

3. ✅ **Documentation complète**
   - Ce fichier explique la solution

## Fichiers devenus obsolètes

Ces fichiers créés précédemment peuvent être supprimés :

- ❌ `tester.yml` (plus nécessaire !)
- ❌ `tester.yml.dist` (plus nécessaire !)
- ❌ `src/Config/TesterConfigFileLocator.php` (remplacé par l'override)
- ❌ `src/Config/TesterConfigLocator.php` (remplacé par l'override)
- ❌ `src/Script/InstallTesterConfig.php` (plus nécessaire)
- ❌ `src/Script/TesterConfigAutoSetup.php` (plus nécessaire)

## Migration pour les utilisateurs existants

Si des utilisateurs ont déjà créé un `tester.yml`, **pas de problème** :
- L'override cherche d'abord les fichiers de config
- Si `tester.yml` existe, il sera utilisé (comportement normal)
- Si `tester.yml` n'existe pas, le fallback s'active

**Rétrocompatibilité garantie** ✅

## Instructions README mises à jour

```markdown
## Installation

1. Install the adapter:
   ```bash
   composer require --dev raneomik/infection-tester-adapter
   ```

2. Run Infection (no configuration file needed!):
   ```bash
   vendor/bin/infection
   ```

That's it! The adapter uses Tester's conventions (tests/ directory and bootstrap.php) automatically.
```

## Conclusion

**Cette solution PSR-4 override est PARFAITE !** 🎉

- ✅ Pas de fichier tester.yml requis
- ✅ Fonctionne immédiatement après installation
- ✅ Compatible avec tous les frameworks
- ✅ Élégante et maintenable

**C'était une excellente idée !** Merci d'avoir suggéré cette approche.

