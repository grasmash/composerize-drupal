<?php

namespace Grasmash\ComposerConverter\Utility;

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
}
