# Solution : Fichier tester.yml.dist requis par Infection

## 🐛 Problème rencontré

```
The path "..." does not contain any of the requested files:
"tester.xml", "tester.yml", "tester.xml.dist", "tester.yml.dist",
"tester.dist.xml", "tester.dist.yml"
```

## 🔍 Cause

Infection **vérifie obligatoirement l'existence** d'un fichier de configuration pour chaque framework de test, même si le framework n'en a pas besoin.

C'est une vérification hard-codée dans Infection qui ne peut pas être désactivée.

## ✅ Solution

Créer un fichier **vide** `tester.yml.dist` avec juste un commentaire explicatif :

```yaml
# Tester configuration file
#
# Tester uses "convention over configuration" and doesn't require configuration.
# This file exists only to satisfy Infection's config file check.
#
# Tester will automatically:
# - Look for tests in: tests/
# - Use bootstrap file: tests/bootstrap.php
# - Discover test files: *Test.php, *.phpt
#
# For Infection configuration, see infection.json5.dist
```

## 📁 Où placer le fichier ?

### Pour les utilisateurs de l'adapter

À la **racine du projet** (même niveau que `composer.json`) :

```
your-project/
├── composer.json
├── tester.yml.dist          ← ICI
├── infection.json5.dist
├── tests/
│   ├── bootstrap.php
│   └── ...
└── src/
```

### Pour les tests e2e de l'adapter

Dans le dossier du test e2e :

```
tests/e2e/Tester/
├── tester.yml.dist          ← ICI
├── infection.json5
├── tests/
└── src/
```

## 🎯 Clarifications importantes

### Ce que le fichier N'EST PAS

❌ Une vraie configuration
❌ Lu par l'adapter Tester
❌ Utilisé pour configurer quoi que ce soit
❌ Nécessaire pour le fonctionnement de Tester

### Ce que le fichier EST

✅ Un **placeholder** pour Infection
✅ Un fichier **vide** (juste des commentaires)
✅ **Obligatoire** pour passer la vérification d'Infection
✅ Une **limitation technique** d'Infection

## 📚 Documentation mise à jour

### README.md
```markdown
**Minimal setup required:**

1. Create a minimal `tester.yml.dist` file (required by Infection, can be empty):
```yaml
# Tester configuration file
# This file exists only to satisfy Infection's config file check.
```

2. Ensure your tests follow Tester conventions:
   - Tests directory: `tests/`
   - Bootstrap file: `tests/bootstrap.php`
   ...
```

### Fichiers créés

1. **`tester.yml.dist`** (racine du projet)
   - Template pour les utilisateurs
   - Commentaires explicatifs complets

2. **`tests/e2e/Tester/tester.yml.dist`**
   - Pour les tests e2e
   - Permet de lancer Infection dans les tests

3. **`docs/WHY-NO-YAML-CONFIG.md`**
   - Explication complète de la philosophie
   - Comparaison avec autres frameworks

4. **`docs/DECISION-NO-YAML.md`**
   - Décision technique documentée
   - Clarification du fichier placeholder

## 🚀 Résultat

Infection peut maintenant s'exécuter sans erreur :

```bash
✅ vendor/bin/infection --test-framework=tester
```

Le fichier `tester.yml.dist` passe la vérification d'Infection, mais :
- N'est jamais ouvert par l'adapter
- Ne contient aucune configuration
- N'affecte pas le comportement de Tester

**C'est le meilleur compromis entre la philosophie "Convention over Configuration" de Tester et les contraintes techniques d'Infection.**

## 💡 Note pour le futur

Si Infection ajoute un jour la possibilité de déclarer qu'un adapter n'a pas besoin de fichier de config, ce fichier pourra être supprimé. Mais pour l'instant, c'est une nécessité technique.

## ✨ Conclusion

Le fichier `tester.yml.dist` est :
- **Techniquement requis** par Infection
- **Fonctionnellement inutile** pour Tester
- **Philosophiquement un compromis** acceptable

L'adapter Tester reste fidèle à sa philosophie "Convention over Configuration" tout en s'intégrant correctement avec Infection ! 🎯

