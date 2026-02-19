# Comment éviter le fichier tester.yml

## Problème

Infection impose que chaque framework de test ait un fichier de configuration. Le `TestFrameworkConfigLocator` d'Infection cherche ces fichiers et lève une exception si aucun n'est trouvé.

## Pourquoi on ne peut pas surcharger le ConfigLocator

L'architecture d'Infection ne permet pas aux extensions de fournir leur propre `TestFrameworkConfigLocator` :

```php
// Dans Infection\TestFramework\Factory
public function create(string $adapterName, bool $skipCoverage): TestFrameworkAdapter
{
    // ...
    $factory::create(
        // ...
        $this->configLocator->locate($factory::getAdapterName()), // ← Locator global, pas personnalisable
        // ...
    );
}
```

Le `$this->configLocator` est injecté via le container d'Infection et il n'y a pas de mécanisme pour qu'une extension enregistre son propre locator.

## Solutions possibles

### 1. ✅ Fichier tester.yml minimal (Solution recommandée)

Créez un fichier `tester.yml` à la racine de votre projet :

```yaml
# Tester configuration - required by Infection
```

**Avantages :**
- Simple et direct
- Fonctionne immédiatement
- Pas de magie cachée

**Inconvénient :**
- Un fichier supplémentaire (mais minimaliste)

### 2. ⚠️ Retourner composer.json (Solution de contournement - ACTUELLE)

`TesterConfigFileLocator` implémente une stratégie de fallback :
1. Cherche `tester.yml` / `tester.xml`
2. Cherche `tests/bootstrap.php`
3. **Retourne `composer.json`** (toujours présent)
4. Retourne le répertoire `tests/`
5. Retourne le répertoire du projet

**MAIS ATTENTION** : Cette classe `TesterConfigFileLocator` n'est **jamais utilisée** par Infection ! C'est le `TestFrameworkConfigLocator` d'Infection qui est utilisé.

### 3. ❌ Plugin Composer (Complexe, non recommandé)

Créer un plugin Composer qui génère automatiquement `tester.yml` lors de l'installation. Problèmes :
- Complexe à implémenter
- Nécessite `composer-plugin-api`
- Les scripts composer.json de votre package ne s'exécutent pas dans les projets utilisateurs

### 4. ❌ Monkey-patching (Impossible)

On ne peut pas modifier le comportement d'Infection sans forker le projet.

## La vraie question : Pourquoi cette classe TesterConfigFileLocator existe-t-elle ?

**Réponse** : Elle a été créée en anticipation d'une fonctionnalité future d'Infection qui permettrait aux extensions de fournir leur propre config locator. Pour l'instant, elle sert de documentation du comportement souhaité.

## Solution finale recommandée

**Pour les développeurs de l'adapter** :
- Fournir un template `tester.yml.dist` à la racine du projet
- Documenter clairement dans le README qu'il faut créer ce fichier
- Expliquer pourquoi c'est nécessaire

**Pour les utilisateurs** :
```bash
# Option 1 : Copier le template
cp vendor/raneomik/infection-tester-adapter/tester.yml.dist tester.yml

# Option 2 : Créer manuellement
echo "# Tester configuration - required by Infection" > tester.yml

# Option 3 : Fichier avec contenu explicatif
cat > tester.yml << 'EOF'
# Tester configuration file
#
# Tester uses "convention over configuration" and doesn't require configuration.
# This file exists only to satisfy Infection's config file check.
#
# Tester will automatically:
# - Look for tests in: tests/
# - Use bootstrap file: tests/bootstrap.php
# - Discover test files: *Test.php, *.phpt
EOF
```

## Conclusion

**Il n'est pas possible de se passer complètement du fichier `tester.yml`** avec l'architecture actuelle d'Infection, SAUF si :

1. Infection modifie son API pour permettre aux extensions de fournir leur propre `TestFrameworkConfigLocator`
2. Infection assouplit la vérification pour permettre des frameworks sans fichier de configuration

En attendant, un fichier `tester.yml` minimal est la solution la plus simple et la plus robuste.

## Proposition d'amélioration pour Infection

Si vous souhaitez contribuer à Infection, vous pourriez proposer :

```php
interface TestFrameworkAdapterFactory
{
    // ...
    
    // Nouvelle méthode optionnelle
    public static function getConfigLocator(): ?TestFrameworkConfigLocatorInterface;
}
```

Puis dans `Infection\TestFramework\Factory` :

```php
$configLocator = $factory::getConfigLocator() ?? $this->configLocator;
$configPath = $configLocator->locate($factory::getAdapterName());
```

Cela permettrait à Tester (et autres frameworks) de fournir leur propre logique de localisation.

