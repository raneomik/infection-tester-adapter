# Guide rapide - Infection Tester Adapter

## 🚀 Démarrage rapide

### Installation
```bash
cd tests/e2e/Tester
composer install --prefer-stable
```

### Vérification
```bash
php check-stable.php
```

### Test
```bash
vendor/bin/infection --test-framework=tester --dry-run
```

## 📁 Structure

```
tester-adapter/
├── src/                          # Code source de l'adapter
│   ├── TesterAdapter.php         # Adapter principal
│   ├── TesterAdapterFactory.php  # Factory
│   ├── Command/                  # Construction des commandes
│   ├── Config/                   # Configuration mutations
│   └── Coverage/                 # Gestion de la couverture
├── tests/
│   ├── phpunit/                  # Tests unitaires
│   └── e2e/Tester/              # Tests e2e (voir README)
├── composer.json                 # Dépendances principales
└── REFACTOR-TEMPLATES-SUMMARY.md # Détails des modifications
```

## 🔧 Configuration

### Dépendances principales (`composer.json`)
- `infection/abstract-testframework-adapter` : Interface de base
- `infection/include-interceptor` : Interception des mutations
- `phpunit/php-code-coverage` : Couverture de code
- `sebastian/diff` : Comparaison (requis par Infection)
- `symfony/yaml` : Configuration Tester
- `symfony/filesystem` & `symfony/process` : Utilitaires

### Tests e2e (`tests/e2e/Tester/composer.json`)
- `infection/infection: ^0.32.0` : **Version stable** (important !)
- `prefer-stable: true` : Force les versions stables

## 🐛 Problèmes courants

### Erreur: Class "SebastianBergmann\Diff\Differ" not found
**Cause :** Infection en version `@dev` avec dépendances instables

**Solution :**
```bash
cd tests/e2e/Tester
rm -rf vendor composer.lock
composer install --prefer-stable
```

### Erreur: Bootstrap file not found
**Normal :** Le bootstrap est optionnel dans Tester. Créez `tests/bootstrap.php` si nécessaire.

## 📚 Documentation

- `REFACTOR-TEMPLATES-SUMMARY.md` - Détails des modifications des templates
- `tests/e2e/Tester/README.md` - Guide des tests e2e
- `check-stable.php` - Script de vérification de l'installation

## ✅ Ce qui a été nettoyé

### Templates simplifiés
- ✅ `MutationBootstrapTemplate` : Suppression des `require_once` inutiles
- ✅ `MutationConfigBuilder` : Nettoyage des méthodes obsolètes
- ✅ Bootstrap optionnel : Plus d'exception si absent

### Scripts de test
- ✅ Suppression des scripts temporaires
- ✅ Garde uniquement `check-stable.php` (utile)

### Namespaces
- ✅ Tout utilise `Raneomik\InfectionTestFramework\Tester`
- ✅ Pas de conflit avec `Infection\TestFramework\Tester`

## 🎯 Prochaines étapes

1. **Tester localement**
   ```bash
   cd tests/e2e/Tester
   php check-stable.php
   vendor/bin/infection --test-framework=tester
   ```

2. **Publier sur GitHub**
   - Le projet est prêt
   - Pas de conflit de namespace avec Infection
   - Versions stables uniquement

3. **Soumettre à Packagist**
   - Package name : `raneomik/infection-tester-adapter`
   - Type : `infection-extension`

