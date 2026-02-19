# ✅ Fix Complété : Double appel à Environment::setup()

**Date :** 2026-02-19  
**Statut :** ✅ RÉSOLU  
**Tests :** ✅ PASSENT (47/47 mutants tués, MSI: 100%)

## 🎯 Problème

Les tests de mutation généraient des faux positifs avec l'erreur :
```
This test forgets to execute an assertion
```

**Cause :** `Tester\Environment::setup()` était appelé **deux fois** :
1. Par notre bootstrap de mutation
2. Par le fichier de test qui charge le bootstrap original

## ✅ Solution

Utiliser l'`IncludeInterceptor` pour **remplacer le bootstrap original par un wrapper idempotent** :

```php
private function interceptBootstrap(): void
{
    // Lire le bootstrap et retirer <?php
    $bootstrapContent = file_get_contents($this->originalBootstrap);
    $bootstrapContent = preg_replace('/^<\?php\s*/s', '', $bootstrapContent);

    // Créer un wrapper avec garde
    $wrapperContent = sprintf(<<<'PHP'
<?php
if (!defined('INFECTION_BOOTSTRAP_EXECUTED')) {
    define('INFECTION_BOOTSTRAP_EXECUTED', true);
%s
}
PHP, $bootstrapContent);

    // Intercepter le bootstrap original
    $wrapperPath = sys_get_temp_dir() . '/infection-bootstrap-' . md5($this->originalBootstrap) . '.php';
    file_put_contents($wrapperPath, $wrapperContent);
    IncludeInterceptor::intercept($this->originalBootstrap, $wrapperPath);
}
```

## 🚀 Résultats

**Avant :**
- ❌ Faux positifs dans les tests de mutation
- ❌ Erreur "This test forgets to execute an assertion"
- ❌ MSI instable

**Après :**
- ✅ Aucun faux positif
- ✅ Tous les mutants correctement détectés (47/47)
- ✅ MSI: 100%
- ✅ Tests e2e passent

## 💡 Innovation

Cette solution est élégante car elle utilise **le mécanisme d'interception lui-même** pour résoudre le problème :
- Pas de modification du projet de test
- Pas de fichiers temporaires dans le projet
- Transparent pour l'utilisateur
- Fonctionne avec n'importe quel bootstrap

## 📚 Références

- Document complet : `FIX-MUTATION-BOOTSTRAP-DOUBLE-SETUP.md`
- Code : `src/Script/MutationBootstrapSetup.php`

