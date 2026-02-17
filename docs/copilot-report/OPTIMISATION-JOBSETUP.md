# ✅ Optimisation JobSetup - Driver Coverage Intelligent

## 🎯 Problème identifié

### Avant
```php
// JobSetup activait TOUS les drivers en même temps
$runner->addPhpIniOption('pcov.enabled', '1');
$runner->addPhpIniOption('xdebug.mode', 'coverage');
// Conflits potentiels + surcharge inutile
```

**Problèmes** :
- ❌ Active PCOV + Xdebug simultanément (conflit)
- ❌ Configure des drivers non disponibles (PCOV sur système sans PCOV)
- ❌ Surcharge inutile des INI options
- ❌ Pas de vérification de disponibilité

## ✅ Solution implémentée

### Détection intelligente du driver

```php
private static function detectCoverageDriver(): ?string
{
    if (extension_loaded('pcov')) return 'pcov';
    if (PHP_SAPI === 'phpdbg') return 'phpdbg';
    if (extension_loaded('xdebug')) return 'xdebug';
    return null;
}
```

### Configuration ciblée

```php
match ($driver) {
    'pcov' => self::configurePcov($runner, $pcovDir),
    'phpdbg' => self::configurePhpdbg($runner),
    'xdebug' => self::configureXdebug($runner),
};
```

**Chaque driver a sa propre méthode de configuration** :
- `configurePcov()` : Active PCOV avec directory si fourni
- `configurePhpdbg()` : Rien à faire (SAPI)
- `configureXdebug()` : Active le mode coverage

## 🚀 Avantages

✅ **Un seul driver actif** - Pas de conflit
✅ **Détection automatique** - Priorité PCOV > PHPDBG > Xdebug
✅ **Configuration ciblée** - Seulement les INI nécessaires
✅ **Code propre** - Match expression moderne (PHP 8.0+)
✅ **Maintenable** - Chaque driver isolé dans sa méthode

## 📊 Impact Performance

**Avant** : 6 INI options configurées (dont certaines inutiles)
**Après** : 2-3 INI options (seulement celles nécessaires)

**Avant** : Conflits potentiels entre drivers
**Après** : Un seul driver actif, zéro conflit

## 🧪 Test

```bash
cd tests/e2e/Tester
composer update raneomik/infection-tester-adapter
vendor/bin/infection --test-framework=tester --dry-run
```

Le driver utilisé apparaît dans les logs Infection :
```
[notice] You are running Infection with PCOV enabled.
```

## 📝 Hiérarchie des drivers (comme Tester)

1. **PCOV** - Le plus rapide
2. **PHPDBG** - Intégré à PHP
3. **Xdebug** - Fallback universel

Cette hiérarchie suit la philosophie de Nette Tester.

## 🎉 Résultat

Configuration intelligente, performante et sans conflit ! Le JobSetup ne configure QUE ce qui est nécessaire selon le driver disponible.

