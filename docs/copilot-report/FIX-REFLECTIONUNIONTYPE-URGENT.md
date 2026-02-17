# 🔥 FIX URGENT - ReflectionUnionType Error

## ⚠️ Problème

L'erreur `Expected an instance of ReflectionNamedType. Got: ReflectionUnionType` persiste malgré les mises à jour du `composer.json`.

## 🎯 Cause

Infection 0.32 force probablement une ancienne version de `webmozart/assert` (1.x) qui ne supporte pas les union types de PHP 8.5.

## ✅ Solution Garantie

### Étape 1 : Forcer la mise à jour dans tests/e2e/Tester

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# Supprimer complètement vendor et composer.lock
rm -rf vendor composer.lock

# Réinstaller avec contrainte stricte
composer install

# Vérifier la version installée
composer show webmozart/assert
```

**Résultat attendu** : `versions : * 2.1.x` ou supérieur

### Étape 2 : Si webmozart/assert est toujours en 1.x

Si après l'étape 1, `webmozart/assert` est toujours en version 1.x, c'est qu'Infection force cette version. Solution :

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# Forcer la mise à jour avec toutes les dépendances
composer update webmozart/assert --with-all-dependencies

# Vérifier à nouveau
composer show webmozart/assert
```

### Étape 3 : Si ça ne marche toujours pas - Utiliser PHP 8.3

PHP 8.5 est très récent et webmozart/assert peut ne pas être totalement compatible. Solution temporaire :

```bash
# Vérifier si PHP 8.3 est disponible
php8.3 -v

# Si oui, utiliser PHP 8.3
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester
php8.3 $(which composer) install
php8.3 vendor/bin/infection --test-framework=tester
```

### Étape 4 : Solution alternative - Downgrade temporaire Infection

Si vraiment rien ne fonctionne, tester avec une version plus ancienne d'Infection :

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# Éditer composer.json : remplacer "infection/infection": "^0.32" par "^0.31"
nano composer.json

# Puis
rm -rf vendor composer.lock
composer install
```

## 🧪 Test Final

Une fois la mise à jour effectuée :

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# 1. Tests Tester doivent passer
vendor/bin/tester tests/

# 2. Infection doit fonctionner SANS l'erreur ReflectionUnionType
vendor/bin/infection --test-framework=tester --threads=1 --debug 2>&1 | grep -i "reflectionuniontype"

# Si la commande ci-dessus ne retourne RIEN, c'est bon !
# Si elle retourne l'erreur, essayez l'étape 3 (PHP 8.3)
```

## 🔍 Diagnostic Détaillé

Pour comprendre exactement ce qui se passe :

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# Voir toutes les versions installées
composer show | grep -E "webmozart|infection"

# Voir qui requiert webmozart/assert
composer why webmozart/assert

# Voir toutes les contraintes
composer depends webmozart/assert
```

## 📊 Matrice de compatibilité

| PHP Version | webmozart/assert | Infection | Status |
|-------------|------------------|-----------|--------|
| 8.2         | 1.x              | 0.32      | ✅ OK  |
| 8.3         | 1.x ou 2.x       | 0.32      | ✅ OK  |
| 8.4         | 2.x              | 0.32      | ✅ OK  |
| 8.5         | **2.1+**         | 0.32      | ⚠️ Nécessite webmozart/assert 2.1+ |

## 🚨 Si RIEN ne fonctionne

Dernière solution - patcher webmozart/assert directement :

```bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester

# Installer le plugin de patches
composer require cweagans/composer-patches --dev

# Créer le fichier patches/webmozart-assert-php85.patch
# (contenu du patch à créer si nécessaire)
```

## 💡 Recommandation Finale

**La solution la plus simple et la plus rapide** : **Utiliser PHP 8.3**

```bash
# Vérifier la version PHP actuelle
php -v

# Si PHP 8.5, switcher vers 8.3
update-alternatives --list php
sudo update-alternatives --set php /usr/bin/php8.3

# Ou avec phpbrew
phpbrew use 8.3

# Puis réinstaller
cd tests/e2e/Tester
rm -rf vendor composer.lock
composer install
vendor/bin/infection --test-framework=tester
```

## ✅ Commande Tout-en-Un

```bash
#!/bin/bash
cd /home/marek/Projects/nette-frankenphp/libs/infection/tester-adapter/tests/e2e/Tester
rm -rf vendor composer.lock
composer install
echo "Version webmozart/assert:"
composer show webmozart/assert | grep versions
echo ""
echo "Test Infection:"
vendor/bin/infection --test-framework=tester --threads=1 2>&1 | head -50
```

Copiez-collez cette commande entière dans votre terminal et regardez le résultat.

---

**Date** : 2026-02-12
**Statut** : Fix à appliquer manuellement
**Priorité** : HAUTE - Utiliser PHP 8.3 si problème persiste

