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
* Generate a new `composer.json` in the `[composer-root]` directory based on [template.composer.json](composer.template.json)
    * Populate `require` with an entry for `drupal/core`
    * Populate `require` with an entry for each project in:
        * `[drupal-root]/modules/contrib`
        * `[drupal-root]/themes/contrib`
        * `[drupal-root]/profiles/contrib`
* Create or modify `[composer-root]/.gitignore]` with entries for Composer-managed contributed projects as [per best practices](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md). You can modify `.gitignore` after composerization if you'd prefer not to follow this practice.
* Executed `composer update` to generate `composer.lock`, autoload files, and install all dependencies in the correct locations.

## Options

* `--composer-root`: Specifies the root directory of your project where `composer.jso` will be generated. This should be the root of your Git repository, where `.git` is located.
* `--drupal-root`: Specifies the Drupal root directory where `index.php` is located.
* `--no-update`: Prevents `composer update` from being automatically run after `composer.json` is generated.
* `--exact-versions`: Will cause Drupal core and contributed projects (modules, themes, profiles) with exact verions constraints in `composer.json` rather than using the default caret operator. E.g., a Drupal core would be required as `8.4.4` rather than `^8.4.4`. This prevents projects from being updated.
