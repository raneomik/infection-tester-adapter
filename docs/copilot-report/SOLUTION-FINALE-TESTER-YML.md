# Solution Finale : Fichier tester.yml obligatoire
## Résumé
**Il est IMPOSSIBLE de se passer du fichier `tester.yml` avec l'architecture actuelle d'Infection.**
## Pourquoi ?
Infection impose que chaque framework de test ait un fichier de configuration et vérifie son existence avant d'appeler votre adapter.
```php
// Code d'Infection qui ne peut pas être contourné
$configPath = $this->configLocator->locate($factory::getAdapterName());
```
Ce `configLocator` est global et ne peut pas être surchargé par une extension.
## Solution appliquée
### 1. Fichier minimal créé
**Emplacement** : `tests/e2e/Tester/tester.yml`
**Contenu** :
```yaml
# Tester configuration - required by Infection
```
**Taille** : 47 bytes (une seule ligne)
### 2. Template fourni
**Fichier** : `tester.yml.dist` à la racine du projet
Les utilisateurs peuvent le copier :
```bash
cp vendor/raneomik/infection-tester-adapter/tester.yml.dist tester.yml
```
### 3. Documentation mise à jour
Le README explique clairement :
- Pourquoi ce fichier est nécessaire
- Comment le créer
- Que Tester ne l'utilise pas (c'est uniquement pour Infection)
## Classes créées mais non utilisées
Ces classes ont été créées en explorant des solutions alternatives, mais ne sont pas utilisées car Infection n'offre pas de mécanisme pour les intégrer :
1. **src/Config/TesterConfigFileLocator.php** - Locator personnalisé
2. **src/Config/TesterConfigLocator.php** - Alternative
3. **src/Script/InstallTesterConfig.php** - Script Composer
4. **src/Script/TesterConfigAutoSetup.php** - Helper d'auto-setup
**Recommandation** : Ces fichiers peuvent être conservés comme preuve de concept ou supprimés pour simplifier le code.
## Avantages de cette solution
✅ **Simple** - Une ligne de commentaire suffit
✅ **Transparent** - Le fichier est ignoré par Tester
✅ **Documenté** - Les utilisateurs comprennent pourquoi
✅ **Fonctionne** - Résout l'erreur d'Infection
## Instructions pour les utilisateurs
Ajoutez ceci dans votre projet :
```bash
# Méthode 1 : Fichier minimal
echo "# Tester configuration - required by Infection" > tester.yml
# Méthode 2 : Copier le template
cp vendor/raneomik/infection-tester-adapter/tester.yml.dist tester.yml
```
C'est tout !
## Proposition d'amélioration pour Infection
Si vous souhaitez contribuer à Infection, proposez une PR pour permettre aux extensions de fournir leur propre `TestFrameworkConfigLocator` :
```php
interface TestFrameworkAdapterFactory
{
    public static function getConfigLocator(): ?TestFrameworkConfigLocatorInterface;
}
```
Cela permettrait à Tester (et autres frameworks "convention over configuration") de fonctionner sans fichier de configuration.
