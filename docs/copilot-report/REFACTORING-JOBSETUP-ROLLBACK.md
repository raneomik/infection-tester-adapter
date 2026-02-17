# ❌ Refactoring JobSetup - Rollback

## 🎯 L'idée initiale

Supprimer le script `--setup` et passer les options `-d` directement à PHP.

## ❌ Pourquoi ça n'a pas fonctionné

### Problème architectural

**Tester fonctionne avec un Runner configuré via `--setup`** :

```php
// Ce que Tester attend :
--setup=script.php

// Dans le script :
$runner->addPhpIniOption('option', 'value');
```

Le `Runner` de Tester **applique les options INI à chaque job/thread** qu'il lance.

### Ce que j'ai essayé

Passer les options directement dans le wrapper PHP :

```php
$phpOptions = ['-d', 'pcov.enabled=1', '-d', 'auto_prepend_file=...'];
// Puis générer le wrapper avec ces options
```

**Résultat** : "No source code was executed"

### Pourquoi ça échoue

1. Le wrapper PHP lance Tester
2. Tester lance des sub-processes (jobs) pour exécuter les tests
3. Les options `-d` passées au wrapper **ne sont pas transmises aux sub-processes**
4. Les jobs Tester n'ont pas les options de couverture
5. Pas de couverture collectée

### La bonne architecture

```
Wrapper PHP
  → Lance Tester avec --setup=script.php
    → Tester charge le script
      → Script configure le $runner
        → $runner->addPhpIniOption(...)  ← Ces options sont appliquées à TOUS les jobs
    → Tester lance N jobs (threads)
      → Chaque job hérite des options du runner
        → Couverture collectée ✅
```

## ✅ Solution adoptée

**Garder le script setup** mais l'améliorer :

### JobSetup optimisé

```php
final class JobSetup
{
    public static function configure(Runner $runner, string $prependFile, ?string $pcovDir): void
    {
        // Utilise CoverageDriverDetector pour la détection
        $driver = CoverageDriverDetector::detect();

        // Configure UNIQUEMENT le driver détecté
        match ($driver) {
            'pcov' => self::configurePcov($runner, $pcovDir),
            'phpdbg' => self::configurePhpdbg($runner),
            'xdebug' => self::configureXdebug($runner),
        };
    }
}
```

**Avantages** :
- ✅ Utilise `CoverageDriverDetector` (code réutilisable)
- ✅ Garde le script setup (nécessaire pour Tester)
- ✅ Configure uniquement le driver disponible
- ✅ Fonctionne avec l'architecture de Tester

## 📊 Comparaison

### Tentative (ne fonctionne pas)
```
Wrapper PHP avec -d options
  → Tester
    → Jobs (sans les options -d) ❌
```

### Solution (fonctionne)
```
Wrapper PHP
  → Tester --setup=script.php
    → Script configure $runner
      → Jobs (avec les options) ✅
```

## 🎯 Leçons

1. **Comprendre l'architecture du framework** avant de refactorer
2. Tester utilise un `Runner` qui **doit** être configuré via `--setup`
3. Les options PHP du wrapper ne sont **pas héritées** par les sub-processes
4. Le script setup n'est **pas de la complexité inutile**, c'est **requis** par Tester

## ✅ Ce qui a été gardé

- `CoverageDriverDetector` : détection réutilisable ✅
- `JobSetup` amélioré : utilise le detector ✅
- Script setup : **nécessaire** pour Tester ✅
- `Preprocessor::preparePrependScript()` : méthode simplifiée disponible ✅

## 📝 Résultat final

**Le code est optimisé** (détection intelligente du driver) **mais garde l'architecture de Tester** (script setup nécessaire).

**C'était une bonne idée en théorie, mais incompatible avec le fonctionnement de Tester.**

