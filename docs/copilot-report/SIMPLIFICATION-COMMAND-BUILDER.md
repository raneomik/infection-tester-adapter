# ✅ SIMPLIFICATION - Utilisation des options natives de Tester

**Date:** 2026-02-19  
**Motivation:** Simplifier la construction de commande en utilisant les options `-p` et `-d` de Tester  
**Impact:** Code beaucoup plus simple, moins d'edge cases, plus maintenable

## 🎯 Problème avant

La construction de la commande était complexe :
```bash
# Avant
/usr/bin/php8.5 -d pcov.enabled=1 -d auto_prepend_file=... /path/vendor/bin/tester -o junit:...
```

Cela nécessitait :
- Résoudre `vendor/bin/tester` → `vendor/nette/tester/src/tester.php` (car wrapper Composer)
- Gérer les cas spéciaux (batch files, phpdbg, CLI vs CGI)
- Cache de la ligne de commande PHP
- Logique complexe dans `CommandLineBuilder`

## ✅ Solution : Options natives de Tester

Tester supporte nativement :
```
-p <path>           Specify PHP interpreter to run (default: php)
-d <key=value>...   Define INI entry 'key' with value 'value'
```

Nouvelle commande :
```bash
# Après
/path/vendor/bin/tester -p /usr/bin/php8.5 -d pcov.enabled=1 -d auto_prepend_file=... -o junit:...
```

Avantages :
- ✅ **Plus besoin de résoudre `tester.php`** - on utilise directement `vendor/bin/tester`
- ✅ **Tester gère lui-même l'exécution PHP** - pas de `proc_open()` à gérer
- ✅ **Code beaucoup plus simple** - moins de logique conditionnelle

## 📝 Fichiers simplifiés

### 1. `src/Command/CommandLineBuilder.php`

**Avant:** 145 lignes avec logique complexe (batch files, phpdbg, CLI, caching)

**Après:** ~60 lignes avec logique simple

```php
public function build(string $testFrameworkExecutable, array $phpExtraArgs, array $frameworkArgs): array
{
    $command = [$testFrameworkExecutable];

    // Add PHP interpreter if we have extra PHP options
    if ([] !== $phpExtraArgs) {
        $command[] = '-p';
        $command[] = $this->findPhp();
    }

    // Merge all arguments
    return array_merge($command, $phpExtraArgs, $frameworkArgs);
}
```

**Supprimé :**
- `isBatchFile()` - plus nécessaire
- `cachedPhpCmdLine` - plus de cache nécessaire
- Logique conditionnelle complexe pour CLI/CGI/phpdbg
- Gestion spéciale des wrappers

### 2. `src/TesterAdapter.php`

**Supprimé :**
- Résolution de `tester.php` depuis `vendor/bin/tester`
- Imports : `dirname`, `is_file`, `str_contains`

**Simplifié :**
```php
// Avant
$testerExecutable = $this->testFrameworkExecutable;
if ([] !== $phpExtraArgs && str_contains($testerExecutable, 'vendor/bin/tester')) {
    $realTester = dirname($testerExecutable, 3) . '/nette/tester/src/tester.php';
    if (is_file($realTester)) {
        $testerExecutable = $realTester;
    }
}
return $this->commandLineBuilder->build($testerExecutable, $phpExtraArgs, $testerArgs);

// Après
return $this->commandLineBuilder->build(
    $this->testFrameworkExecutable,
    $phpExtraArgs,
    $testerArgs
);
```

## 📊 Bénéfices

### Lignes de code
- **CommandLineBuilder:** 145 → ~60 lignes (**-59%**)
- **TesterAdapter:** Suppression de 10+ lignes de workaround

### Complexité
- **Moins d'edge cases** : Plus de gestion de batch files, phpdbg, etc.
- **Plus de workaround** : Plus besoin de résoudre `tester.php`
- **Logique unifiée** : Même approche pour tous les cas

### Maintenabilité
- ✅ Code plus facile à comprendre
- ✅ Moins de bugs potentiels
- ✅ Suit les conventions de Tester (utilise ses options natives)

## 🔗 Compatibilité

Cette simplification fonctionne avec :
- ✅ Tester 2.x (supporte `-p` et `-d`)
- ✅ Tous les environnements (Linux, macOS, Windows WSL)
- ✅ PHP 8.5+
- ✅ Infection 0.32+

## 🎉 Résultat

Le code est maintenant :
- **Plus simple** : Moins de lignes, moins de logique
- **Plus robuste** : Utilise les capacités natives de Tester
- **Plus maintenable** : Facile à comprendre et modifier
- **Sans workarounds** : Plus de hacks pour gérer les wrappers Composer

## 📚 Prochaines étapes possibles

D'autres simplifications pourraient être envisagées :
1. **`InitialTestRunCommandBuilder`** - Peut-être aussi simplifiable avec `-p` et `-d`
2. **`CoverageDriverDetector`** - Vérifier si toujours nécessaire
3. **Configuration** - Simplifier les builders de config

---

**Conclusion:** En utilisant les options natives de Tester (`-p` et `-d`), nous avons simplifié drastiquement le code tout en le rendant plus robuste et maintenable. C'est un excellent exemple de "utiliser les outils comme prévu plutôt que de créer des workarounds".

