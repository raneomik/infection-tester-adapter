# Exemple d'utilisation - Tester Adapter

## Installation

```bash
composer require --dev infection/infection infection/tester-adapter
```

## Configuration minimale

### 1. Fichier `tester.yml` (configuration Nette Tester)

```yaml
php:
    binary: php
    ini:
        - memory_limit=512M

paths:
    tests: tests/
    src: src/

coverage:
    include:
        - src/**/*.php
```

### 2. Fichier `infection.json5` (configuration Infection)

```json5
{
    "$schema": "vendor/infection/infection/resources/schema.json",
    "source": {
        "directories": ["src"]
    },
    "logs": {
        "text": "var/infection/infection.log"
    },
    "mutators": {
        "@default": true
    },
    "testFramework": "tester",
    "testFrameworkOptions": ""
}
```

## Utilisation

### Exécution basique

```bash
vendor/bin/infection
```

### Avec options

```bash
# Avec threads parallèles
vendor/bin/infection --threads=4

# Afficher les mutations
vendor/bin/infection --show-mutations

# Tester seulement les fichiers modifiés
vendor/bin/infection --git-diff-lines --git-diff-base=main

# Mode debug
vendor/bin/infection --debug
```

## Comment ça marche (internals)

### 1. Infection charge l'adapter

```php
// Automatique via infection/extension-installer
$factory = new TesterAdapterFactory();
$adapter = $factory->create(
    testFrameworkExecutable: 'vendor/bin/tester',
    tmpDir: 'var/infection/infection',
    testFrameworkConfigPath: 'tester.yml',
    // ... autres paramètres
);
```

### 2. Tests initiaux

```php
// Adapter génère la commande
$command = $adapter->getInitialTestRunCommandLine('', [], false);

// Internals :
// 1. Preprocessor::prepareJobScripts() crée setup.php et prepend.php
// 2. Wrapper bash exécute : vendor/bin/tester --setup setup.php
// 3. Chaque job Tester auto-prepend prepend.php (via ini)
// 4. CoverageRuntime collecte la couverture dans chaque job
// 5. Fragments .phpser sont créés
// 6. MergePostProcessor fusionne les fragments
// 7. index.xml (coverage) et junit.xml sont créés
```

### 3. Mutations

Pour chaque mutant :

```php
$command = $adapter->getMutantCommandLine(
    coverageTests: $tests,
    mutatedFilePath: '/tmp/mutant_abc123.php',
    mutationHash: 'abc123',
    mutationOriginalFilePath: 'src/Calculator.php',
    extraOptions: ''
);

// Internals :
// 1. Wrapper bash backup src/Calculator.php
// 2. Copy mutant → src/Calculator.php
// 3. Execute vendor/bin/tester avec tests spécifiques
// 4. Restore src/Calculator.php
// 5. Analyse le résultat (escaped/killed/error)
```

## Collecte de couverture

L'adapter utilise **phpunit/php-code-coverage** avec auto-détection du driver :

1. **PCOV** (préféré - le plus rapide)
   ```bash
   pecl install pcov
   php -d pcov.enabled=1 -d pcov.directory=src vendor/bin/tester
   ```

2. **PHPDBG** (natif PHP)
   ```bash
   phpdbg -qrr vendor/bin/tester
   ```

3. **Xdebug** (fallback)
   ```bash
   php -d xdebug.mode=coverage vendor/bin/tester
   ```

L'adapter détecte automatiquement le driver disponible et configure Tester en conséquence.

## Structure des fichiers générés

```
var/infection/infection/
├── run-initial-tester.sh           # Wrapper bash pour tests initiaux
├── tester_job_setup.php            # Configure le runner Tester
├── tester_job_prepend.php          # Collecte la couverture (auto_prepend)
├── coverage-fragments/             # Fragments de couverture par job
│   ├── cc-12345-abcd.phpser
│   ├── cc-12346-bcde.phpser
│   └── ...
├── index.xml                       # Coverage XML (format Clover)
├── junit.xml                       # Tests results (format JUnit)
└── mutations/                      # Mutants générés
    ├── run-mutant-abc123.sh
    ├── ...
```

## Debugging

### Vérifier les fragments de couverture

```bash
ls -lh var/infection/infection/coverage-fragments/

# Inspecter un fragment
php -r "
\$data = file_get_contents('var/infection/infection/coverage-fragments/cc-*.phpser');
\$cc = unserialize(\$data, ['allowed_classes' => true]);
var_dump(get_class(\$cc));
"
```

### Vérifier le JUnit normalisé

```bash
cat var/infection/infection/junit.xml | xmllint --format -
```

### Vérifier le coverage XML

```bash
cat var/infection/infection/index.xml | head -50
```

### Lancer manuellement le wrapper

```bash
bash -x var/infection/infection/run-initial-tester.sh
```

## Problèmes courants

### "No coverage data found"

**Cause** : Aucun driver de couverture disponible

**Solution** : Installer PCOV
```bash
pecl install pcov
php -m | grep pcov
```

### "Tests must be in a passing state"

**Cause** : Les tests Tester échouent

**Solution** : Vérifier que les tests passent normalement
```bash
vendor/bin/tester tests/
```

### "Expected namespace URI"

**Cause** : JUnit XML malformé (ancien problème résolu dans le refactoring)

**Solution** : Le refactoring actuel normalise automatiquement le JUnit XML via `MergePostProcessor::normalizeJUnitXml()`

## Avantages de l'architecture refactorisée

### Avant (ancien système)

```bash
# Beaucoup de variables d'environnement
export INFECTION_TESTER_COVERAGE_FRAGMENT_DIR=/tmp/...
export INFECTION_TESTER_COVERAGE_SRC_DIRS=src
export INFECTION_TESTER_VISIBILITY=protected

# Scripts procéduraux
php resources/make_protected_public.infection.php apply src/
vendor/bin/tester tests/
php resources/make_protected_public.infection.php restore src/
php resources/tester_merge_postprocess.infection.php /tmp/fragments /tmp/out
```

### Après (nouveau système)

```bash
# Tout est transparent !
vendor/bin/infection
```

**Changements invisibles pour l'utilisateur** :
- ✅ Configuration via classes PSR-4
- ✅ Pas de variables d'environnement
- ✅ Pas de transformations AST du code source
- ✅ Tout géré automatiquement par l'adapter

## Performance

Avec PCOV (recommandé) :
- **Tests initiaux** : ~même vitesse que Tester normal
- **Par mutant** : ~10-50ms overhead (backup/restore fichier)
- **Merge coverage** : ~100-500ms selon nombre de fragments

## Compatibilité

- ✅ PHP 8.2+
- ✅ Nette Tester 2.6+
- ✅ Infection 0.32+
- ✅ PCOV / PHPDBG / Xdebug

---

**Documentation officielle** : https://infection.github.io/
**Nette Tester** : https://tester.nette.org/

