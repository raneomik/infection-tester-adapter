# Résumé Final : Impossible de se passer du fichier tester.yml

## Date : 2026-02-18

## Question initiale

**"Oui mais si je veux me passer de tout fichier ?"**

## Réponse courte

**C'est impossible avec l'architecture actuelle d'Infection.** Vous devez créer un fichier `tester.yml` minimal.

## Explication technique

### Pourquoi c'est impossible ?

1. **Infection utilise un ConfigLocator global**
   ```php
   // Dans Infection\Container\Container
   TestFrameworkConfigLocator::class => static fn (self $container): TestFrameworkConfigLocator => 
       new TestFrameworkConfigLocator($container->getProjectDir()),
   ```

2. **Ce locator est partagé par tous les frameworks**
   ```php
   // Dans Infection\TestFramework\Factory::create()
   $factory::create(
       // ...
       $this->configLocator->locate($factory::getAdapterName()), // ← Locator global fixe
       // ...
   );
   ```

3. **Le locator d'Infection vérifie l'existence du fichier**
   ```php
   // Dans TestFrameworkConfigLocator::locate()
   foreach (self::DEFAULT_EXTENSIONS as $extension) {
       $conf = sprintf('%s/%s.%s', $dir, $cliTool, $extension);
       if (file_exists($conf)) {
           return realpath($conf);
       }
   }
   throw FileOrDirectoryNotFound::multipleFilesDoNotExist($dir, $triedFiles);
   ```

4. **Aucun mécanisme pour overrider le locator**
   - Pas de méthode dans `TestFrameworkAdapterFactory` pour fournir un locator personnalisé
   - Le container d'Infection est figé
   - Les extensions ne peuvent pas modifier le comportement du locator

### Pourquoi TesterConfigFileLocator n'est pas utilisé ?

Le `TesterConfigFileLocator` que nous avons créé implémente bien `TestFrameworkConfigLocatorInterface`, MAIS :
- Il n'est jamais instancié par Infection
- Il n'est pas enregistré dans le container d'Infection
- Il n'y a pas de mécanisme d'extension pour le faire utiliser

C'est une **classe dormante** en attente d'une future évolution d'Infection.

## Solutions explorées et rejetées

### ❌ 1. Retourner un fichier existant (composer.json)

**Problème** : `TesterConfigFileLocator` n'est pas utilisé par Infection

### ❌ 2. Plugin Composer qui génère le fichier

**Problème** : Les scripts du package ne s'exécutent pas dans les projets utilisateurs

### ❌ 3. Générer le fichier dynamiquement dans TesterAdapterFactory

**Problème** : `locate()` est appelé **avant** `create()`, trop tard pour intervenir

### ❌ 4. Monkey-patching ou hook

**Problème** : Infection n'offre pas de hooks pour les extensions

## Solution finale : Fichier tester.yml minimal

### ✅ C'est la SEULE solution qui fonctionne

**Créez ce fichier à la racine de votre projet :**

```yaml
# Tester configuration - required by Infection
```

**C'est tout !** Un commentaire suffit.

### Instructions pour les utilisateurs

Ajoutez ceci à votre README :

```markdown
## Installation

1. Install the adapter:
   ```bash
   composer require --dev raneomik/infection-tester-adapter
   ```

2. Create a minimal `tester.yml` file at your project root:
   ```bash
   echo "# Tester configuration - required by Infection" > tester.yml
   ```
   
   Or copy the template:
   ```bash
   cp vendor/raneomik/infection-tester-adapter/tester.yml.dist tester.yml
   ```

3. Run Infection:
   ```bash
   vendor/bin/infection
   ```
```

## Fichiers créés pour tenter de résoudre le problème

1. **src/Config/TesterConfigFileLocator.php** - Locator personnalisé (non utilisé par Infection)
2. **src/Config/TesterConfigLocator.php** - Autre tentative (non utilisé)
3. **src/Script/InstallTesterConfig.php** - Script Composer (ne fonctionne pas pour les utilisateurs)
4. **src/Script/TesterConfigAutoSetup.php** - Helper d'auto-setup (inutile sans mécanisme d'invocation)

**Ces fichiers peuvent être supprimés ou gardés pour référence future.**

## Ce qui a vraiment fonctionné

**Fichiers utiles créés :**
1. ✅ **tester.yml.dist** - Template pour les utilisateurs
2. ✅ **tests/e2e/Tester/tester.yml** - Pour les tests e2e
3. ✅ **Documentation** - README mis à jour avec instructions claires

## Proposition d'amélioration pour Infection

Pour permettre aux extensions de fournir leur propre config locator :

```php
// Dans Infection\AbstractTestFramework\TestFrameworkAdapterFactory
interface TestFrameworkAdapterFactory
{
    // Nouvelle méthode optionnelle
    public static function getConfigLocator(): ?TestFrameworkConfigLocatorInterface;
}

// Dans Infection\TestFramework\Factory::create()
$configLocator = method_exists($factory, 'getConfigLocator') 
    ? ($factory::getConfigLocator() ?? $this->configLocator)
    : $this->configLocator;

$configPath = $configLocator->locate($factory::getAdapterName());
```

Puis dans TesterAdapterFactory :

```php
public static function getConfigLocator(): TestFrameworkConfigLocatorInterface
{
    return new TesterConfigFileLocator();
}
```

## Conclusion

**Réponse à "si je veux me passer de tout fichier ?"**

➡️ **Ce n'est pas possible actuellement.**

**Vous DEVEZ créer un fichier `tester.yml` minimal.**

C'est un compromis acceptable car :
- ✅ Le fichier est trivial (une ligne suffit)
- ✅ Il n'affecte pas le fonctionnement de Tester
- ✅ C'est bien documenté
- ✅ TesterAdapter l'ignore complètement

**Alternative future** : Proposer une PR à Infection pour permettre aux extensions de fournir leur propre config locator.

