<?php

namespace Grasmash\ComposerConverter\Utility;

use Composer\Semver\Semver;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class DrupalInspector
{

    public static function findContribProjects($drupal_root, $subdir, $composer_json)
    {
        if (!file_exists($drupal_root . "/" . $subdir)) {
            return [];
        }

        $finder = new Finder();
        $finder->in([$drupal_root . "/" . $subdir])
            ->name('*.info.yml')
            ->depth('== 1')
            ->files();

        $projects = [];
        foreach ($finder as $fileInfo) {
            $path = $fileInfo->getPathname();
            $filename_parts = explode('.', $fileInfo->getFilename());
            $machine_name = $filename_parts[0];
            $module_info = Yaml::parseFile($path);
            $semantic_version = false;
            // Grab version from module yaml file.
            if (array_key_exists('version', $module_info)) {
                $semantic_version = self::getSemanticVersion($module_info['version']);
            } else {
                // Dev versions of modules do not include version info in yaml files.
                // Look in composer.json for a version constraint.
                if (array_key_exists('drupal/' . $machine_name, $composer_json->require)) {
                    $version_constraint = $composer_json['require']['drupal/' . $machine_name];
                    $semantic_version = self::getSemanticVersion($version_constraint);
                }
            }

            if ($semantic_version === false) {
                $semantic_version = null;
            }
            $projects[$machine_name] = $semantic_version;
        }

        return $projects;
    }

    /**
     * Generates a semantic version for a Drupal project.
     *
     * 3.0
     * 3.0-alpha1
     * 3.12-beta2
     * 4.0-rc12
     * 3.12
     * 1.0-unstable3
     * 0.1-rc2
     * 2.10-rc2
     *
     * {major}.{minor}.0-{stability}{#}
     *
     * @return string
     */
    public static function getSemanticVersion($drupal_version)
    {
        // Strip the 8.x prefix from the version.
        $version = preg_replace('/^8\.x-/', null, $drupal_version);

        if (preg_match('/-dev$/', $version)) {
            return preg_replace('/^(\d).+-dev$/', '$1.x-dev', $version);
        }

        $matches = [];
        preg_match('/^(\d{1,2})\.(\d{0,2})(\-(alpha|beta|rc|unstable)\d{1,2})?$/i', $version, $matches);
        $version = false;
        if (!empty($matches)) {
            $version = "{$matches[1]}.{$matches[2]}.0";
            if (array_key_exists(3, $matches)) {
                $version .= $matches[3];
            }
        }

        // Reject 'unstable'.

        return $version;
    }

    /**
     * @param $version
     *
     * @return string
     */
    public static function getVersionConstraint($version, $exact_versions)
    {
        if ($version == null) {
            return "*";
        }
        elseif (strstr($version, '-dev') !== FALSE) {
            return $version;
        }
        elseif ($exact_versions) {
            return $version;
        }
        else {
            return "^" . $version;
        }
    }

    /**
     * @param $matches
     *
     * @throws \Exception
     */
    public static function determineDrupalCoreVersionFromDrupalPhp($file_contents)
    {
        /**
         * Matches:
         * const VERSION = '8.0.0';
         * const VERSION = '8.0.0-beta1';
         * const VERSION = '8.0.0-rc2';
         * const VERSION = '8.5.11';
         * const VERSION = '8.5.x-dev';
         * const VERSION = '8.6.11-dev';
         */
        preg_match('#(const VERSION = \')(\d\.\d\.(\d{1,}|x)(-(beta|alpha|rc)[0-9])?(-dev)?)\';#', $file_contents, $matches);
        if (array_key_exists(2, $matches)) {
            $version = $matches[2];

            // Matches 8.6.11-dev. This is not actually a valid semantic
            // version. We fix it to become 8.6.x-dev before returning.
            if (strstr($version, '-dev') !== FALSE
              && substr_count($version, '.') == 2) {
                // Matches (core) version 8.6.11-dev.
                $version = str_replace('-dev', '', $version);
                $pos1 = strpos($version, '.');
                $pos2 = strpos($version, '.', $pos1 + 1);
                $version = substr($version, 0, $pos1 + $pos2) . 'x-dev';
            }

            return $version;
        }
    }
}
