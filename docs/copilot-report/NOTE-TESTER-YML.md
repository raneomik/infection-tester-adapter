# Note sur le fichier tester.yml

## Pourquoi ce fichier existe ?

Bien que `TesterAdapter` utilise les **conventions Tester** (répertoire `tests/` et fichier `tests/bootstrap.php`) et n'a **pas besoin de parser** le fichier de configuration, **Infection vérifie l'existence** de ce fichier avant de lancer les tests.

## Infection FileLocator

Infection cherche automatiquement ces fichiers :
- `tester.xml`, `tester.yml`
- `tester.xml.dist`, `tester.yml.dist`
- `tester.dist.xml`, `tester.dist.yml`

Si aucun n'est trouvé, Infection lance une exception `FileOrDirectoryNotFound`.

## Solution

### Option 1 : Fichier minimal (recommandé)

Créer un fichier `tester.yml` vide ou avec un commentaire :

```yaml
# Tester configuration (minimal)
# TesterAdapter uses conventions: tests/ and tests/bootstrap.php
```

### Option 2 : Spécifier le chemin dans infection.json5

```json5
{
  "testFramework": "tester",
  "testFrameworkOptions": {
    "config": "path/to/tester.yml"
  }
}
```

## Convention over Configuration

Le `TesterAdapter` suit le principe **Convention over Configuration** :

- ✅ **Répertoire des tests** : `tests/` (par convention)
- ✅ **Bootstrap** : `tests/bootstrap.php` (optionnel, par convention)
- ✅ **Fichier de config** : Requis par Infection mais **non parsé** par TesterAdapter

Le contenu du fichier `tester.yml` est **ignoré** par TesterAdapter. Il utilise uniquement les conventions Tester.

## Avantages

- ✅ Pas de dépendance à `symfony/yaml`
- ✅ Pas de parsing de configuration
- ✅ Code plus simple et plus rapide
- ✅ Comportement prévisible (conventions standards)

## Pour aller plus loin

Si vous avez une structure non-conventionnelle, vous pouvez :
1. Adapter votre structure pour suivre les conventions Tester
2. Créer des symlinks vers les conventions
3. Contacter les mainteneurs d'Infection pour supporter les projets sans fichier de config

