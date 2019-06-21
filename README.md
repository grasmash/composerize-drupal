[![Build Status](https://travis-ci.org/grasmash/composerize-drupal.svg?branch=master)](https://travis-ci.org/grasmash/composerize-drupal) [![Coverage Status](https://coveralls.io/repos/github/grasmash/composerize-drupal/badge.svg?branch=master)](https://coveralls.io/github/grasmash/composerize-drupal?branch=master) [![Packagist](https://img.shields.io/packagist/v/grasmash/composerize-drupal.svg)](https://packagist.org/packages/grasmash/composerize-drupal)

# Composerize Drupal

_Composerize Drupal_ is a Composer plugin that converts a non-Composer-managed Drupal application (e.g., one created via tarball) to a Composer-managed Drupal application.

It is not for creating new Drupal applications. If you want to create a brand new Drupal application, use [drupal-project](https://github.com/drupal-composer/drupal-project) instead.

## Functionality

The `composerize-drupal` command will perform the following operations:

* Remove all vestigial `composer.json` and `composer.lock` files
* Generate a new `composer.json` in the `[composer-root]` directory based on [template.composer.json](template.composer.json).
    * Populate `require` with an entry for `drupal/core`
    * Populate `require` with an entry for each project in:
        * `[drupal-root]/modules`
        * `[drupal-root]/modules/contrib`
        * `[drupal-root]/themes/contrib`
        * `[drupal-root]/profiles/contrib`
    * Require and configure suggested Composer plugins:
        * Add [Composer Installer](https://github.com/grasmash/composerize-drupal) file paths to `extra` configuration to ensure that Drupal projects are downloaded to the correct locations.
        * Merge dependencies from `[drupal-root]/modules/custom/*/composer.json` into your root dependencies via [Composer Merge](https://github.com/wikimedia/composer-merge-plugin), permitting custom modules to have separate `composer.json` files.
        * Create and populate `extra.patches` object to facilitate patching with [Composer Patches](https://github.com/cweagans/composer-patches). Patches to profiles, themes, and modules will be automatically discovered and moved to the a new [repo-root]/patches directory.
      * Add entries to `repositories`:
        * `https://packages.drupal.org/8` for installing packages from Drupal.org
        * [`https://asset-packagist.org/`](https://asset-packagist.org/) to permit installing NPM packages.
* Create or modify `[composer-root]/.gitignore` with entries for Composer-managed contributed projects as [per best practices](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md). You can modify `.gitignore` after composerization if you'd prefer not to follow this practice.
* Execute `composer update` to generate `composer.lock`, autoload files, and install all dependencies in the correct locations.

It will NOT add any contributed projects in `docroot/libraries` to `composer.json`. You must add those to your `composer.json` file manually. In addition to [packagist](https://packagist.org/) and Drupal.org packages, you may also use any package from [asset packagist](https://asset-packagist.org/), which makes NPM packages available to Composer.

## Installation

```
composer global require grasmash/composerize-drupal
```

## Usage:
```
cd path/to/drupal/project/repo
composer composerize-drupal --composer-root=[repo-root] --drupal-root=[drupal-root]
```

The `[composer-root]` should be the root directory of your project, where `.git` is located.

The `[drupal-root]` should be the Drupal root, where `index.php` is located.

Examples:
```
# Drupal is located in a `docroot` subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=./docroot

# Drupal is located in a `web` subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=./web

# Drupal is located in a `public_html` subdirectory (cPanel compatible).
composer composerize-drupal --composer-root=. --drupal-root=./public_html

# Drupal is located in the repository root, not in a subdirectory.
composer composerize-drupal --composer-root=. --drupal-root=.
```

## Options

* `--composer-root`: Specifies the root directory of your project where `composer.json` will be generated. This should be the root of your Git repository, where `.git` is located.
* `--drupal-root`: Specifies the Drupal root directory where `index.php` is located.
* `--no-update`: Prevents `composer update` from being automatically run after `composer.json` is generated.
* `--no-gitignore`: Prevents modification of the root .gitignore file. 
* `--exact-versions`: Will cause Drupal core and contributed projects (modules, themes, profiles) to be be required with exact verions constraints in `composer.json`, rather than using the default caret operator. E.g., a `drupal/core` would be required as `8.4.4` rather than `^8.4.4`. This prevents projects from being updated. It is not recommended as a long-term solution, but may help you convert to using Composer more easily by reducing the size of the change to your project.
