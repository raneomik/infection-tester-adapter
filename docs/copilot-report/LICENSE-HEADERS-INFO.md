# Configuration PHP-CS-Fixer avec header minimaliste

Si vous préférez un header plus court (approche moderne) :

```php
<?php
/**
 * SPDX-License-Identifier: BSD-3-Clause
 * Copyright (c) 2024 Marek Raneomik
 *
 * For full license text, see LICENSE file in the repository root.
 */
```

## Avantages du header minimaliste SPDX :
- ✅ Légalement valide (reconnu internationalement)
- ✅ Compact (5 lignes au lieu de 35)
- ✅ Référence le LICENSE complet
- ✅ Utilisé par des projets modernes (Symfony, Laravel, etc.)

## Configuration PHP-CS-Fixer pour header court :

```php
$header = <<<'HEADER'
SPDX-License-Identifier: BSD-3-Clause
Copyright (c) 2024 Marek Raneomik

For full license text, see LICENSE file in the repository root.
HEADER;

$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        'header_comment' => [
            'header' => $header,
            'comment_type' => 'comment',
            'location' => 'after_open',
            'separate' => 'both',
        ],
        // ... autres règles
    ]);
```

## Conclusion

**Pour votre projet tester-adapter :**

1. **Minimum requis** : Fichier `LICENSE` à la racine ✅
2. **Recommandé** : Header court SPDX dans chaque fichier ⭐
3. **Maximum** : Header BSD-3-Clause complet (comme actuellement)

**Ce que vous risquez sans header dans les fichiers :**
- ⚠️ Ambiguïté si quelqu'un copie un fichier seul
- ⚠️ Moins professionnel
- ⚠️ Difficile de prouver la licence d'un fichier isolé
- ✅ **Mais légalement couvert** par le LICENSE à la racine

**Ma recommandation :** Utilisez le header SPDX court - c'est le meilleur compromis entre protection et lisibilité.

