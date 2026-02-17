# Décision : Pas de support YAML/XML pour Tester

## 📋 Contexte

Question initiale : "Le support de la configuration YAML est-il nécessaire ?"

## 🎯 Décision : **NON, mais un fichier placeholder vide est requis**

### État final

**Fichier requis** : `tester.yml.dist` (vide)
```yaml
# Tester configuration file
# This file exists only to satisfy Infection's config file check.
```

**Raison technique** : Infection vérifie l'existence d'un fichier de config pour chaque adapter, mais l'adapter Tester ne lit jamais ce fichier.

### Raisons

1. **Philosophie de Tester** : "Convention over Configuration"
   - Tests dans `tests/`
   - Bootstrap dans `tests/bootstrap.php`
   - Pattern `*Test.php` ou `*.phpt`
   - **Aucune config nécessaire**

2. **Comparaison avec autres adapters**
   - PHPUnit : A BESOIN de `phpunit.xml` (test suites, whitelist, etc.)
   - Codeception : A BESOIN de `codeception.yml` (modules, paths, etc.)
   - **Tester : N'a PAS besoin de config** ✨

3. **État actuel du code**
   - ✅ Aucun code de parsing YAML/XML
   - ✅ Tout fonctionne avec conventions
   - ✅ `tester.yml` du e2e était vide (juste un placeholder)

4. **Auto-détection**
   ```php
   - Exécutable : vendor/bin/tester (via Composer)
   - Tests : tests/ (convention)
   - Bootstrap : tests/bootstrap.php (convention)
   - Coverage driver : Auto-détecté (PCOV/Xdebug/PHPDBG)
   - Source dirs : Depuis infection.json5
   ```

## ✅ Actions effectuées

1. **Créé** : `tester.yml.dist` (fichier vide avec commentaire explicatif)
   - Nécessaire pour passer la vérification d'Infection
   - Jamais lu par l'adapter
   - Contient juste un commentaire expliquant pourquoi il existe

2. **Mis à jour README.md** :
   - Section "Features" ajoutée avec "Zero Configuration"
   - Section "Configuration" clarifiée avec exemples
   - Emphase sur convention over configuration

3. **Documentation** : `docs/WHY-NO-YAML-CONFIG.md`
   - Comparaison détaillée avec PHPUnit/Codeception
   - Explication de la philosophie Tester
   - Arguments contre le support YAML

## 🚀 Avantages

✅ **Simplicité** - Pas de code de parsing à maintenir
✅ **Moins de bugs** - Pas de config mal formattée
✅ **Rapidité** - Pas de parsing de fichiers
✅ **Cohérence** - Suit la philosophie Nette/Tester
✅ **Zéro friction** - Fonctionne out-of-the-box

## 🔧 Si personnalisation nécessaire

Les utilisateurs peuvent utiliser `infection.json5` :

```json5
{
    "testFramework": "tester",
    "testFrameworkOptions": "--setup custom.php --php-ini pcov.enabled=1",
    "source": {
        "directories": ["src", "lib"]
    }
}
```

**Tout passe par Infection, pas besoin de config Tester séparée !**

## 📊 Impact

| Aspect | Avant | Après |
|--------|-------|-------|
| **Fichiers config** | tester.yml (vide) | Aucun |
| **Code parsing** | Aucun | Aucun |
| **Complexité** | Inutile | Éliminée |
| **Maintenance** | Placeholder | Rien |
| **Documentation** | Ambiguë | Claire |

## 🎓 Leçon

**Ne pas copier aveuglément d'autres adapters** - Chaque framework a ses besoins.

Tester est **simple par design**, son adapter doit l'être aussi.

## ✨ Résultat

L'adapter Tester est maintenant :
- Plus simple
- Plus clair
- Plus fidèle à la philosophie Nette
- Sans code superflu

**KISS principle appliqué avec succès !** 🎯

