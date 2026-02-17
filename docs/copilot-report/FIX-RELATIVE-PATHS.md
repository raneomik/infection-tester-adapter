# Correction : Chemins relatifs dans JUnit XML

## Problème

Erreur Infection :
```
Expected the query "//testcase[contains(@file, "tester")][1]" to return a "DOMNodeList" with no or one node. Got "5".
```

## Cause

Les chemins absolus des fichiers de test contenaient le mot "tester" :
```
/home/marek/.../tester-adapter/tests/unit/CalculatorTest.php
```

Infection utilise une requête XPath pour identifier le framework de test en cherchant "tester" dans les attributs `file` des testcases. Avec des chemins absolus, **tous les testcases** matchaient la requête (5 testcases au total), alors qu'Infection s'attend à 0 ou 1.

## Solution

**Utilisation de chemins relatifs** dans le JUnit XML généré.

### AVANT (chemins absolus)
```xml
<testcase file="/home/marek/.../tester-adapter/tests/unit/CalculatorTest.php" .../>
```

### APRÈS (chemins relatifs)
```xml
<testcase file="tests/unit/CalculatorTest.php" .../>
```

## Implémentation

Ajout de deux méthodes dans `JUnitFormatter` :

1. **`determineBasePath()`** : Trouve le répertoire racine du projet
   - Cherche le répertoire "tests" ou un `composer.json`
   - Remonte l'arborescence si nécessaire

2. **`makeRelativePath()`** : Convertit un chemin absolu en relatif
   - Supprime le préfixe du base path
   - Retire le slash de début

### Logique

```
Chemin absolu: /home/user/project/tests/unit/Test.php
Base path:     /home/user/project
↓
Chemin relatif: tests/unit/Test.php
```

## Avantages

✅ **Pas de conflit avec le mot "tester"** dans le chemin
✅ **Chemins plus courts** et lisibles
✅ **Portabilité** : Le JUnit XML peut être déplacé
✅ **Compatible Infection** : La requête XPath ne trouve aucun match

## Test

```bash
cd tests/e2e/Tester
vendor/bin/infection --threads=2
```

L'erreur `Got "5"` ne devrait plus apparaître.

## Fichiers modifiés

- `src/Coverage/JUnitFormatter.php`
  - Ajout de `determineBasePath()`
  - Ajout de `makeRelativePath()`
  - Modification de `buildPhpUnitStructure()` pour utiliser des chemins relatifs

---

**Date** : 15 février 2026
**Fix** : Chemins relatifs pour éviter conflits XPath

