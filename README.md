[![Build Status](https://travis-ci.org/grasmash/composerize-drupal.svg?branch=master)](https://travis-ci.org/grasmash/composerize-drupal) [![Coverage Status](https://coveralls.io/repos/github/grasmash/composerize-drupal/badge.svg?branch=master)](https://coveralls.io/github/grasmash/composerize-drupal?branch=master)

# Composerize Drupal

To install:

```
composer global require grasmash/composerize-drupal
```

To use:
```
cd path/to/drupal/project/repo
composer composerize-drupal --composer-root=[repo-root] --drupal-root=[drupal-root]
```

The `[composer-root]` should be the root directory of your project, where `.git` is located.

The `[drupal-root]` should be the Drupal root, where `index.php` is located.

Examples:
```
# Drupal is located in a `docroot` subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=docroot

# Drupal is located in a `web` subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=web

# Drupal is located in the repository root, not in a subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=.
```

The `composerize-drupal` command will perform the following operations:

* Remove all vestigial `composer.json` and `composer.lock` files
