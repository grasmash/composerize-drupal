<?php

namespace Grasmash\ComposerConverter\Utility;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class DrupalInspector
{

    public static function findContribProjects($drupal_root, $subdir)
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
        $invalid_versions = [];
        foreach ($finder as $fileInfo) {
            $path = $fileInfo->getPathname();
            $filename_parts = explode('.', $fileInfo->getFilename());
            $machine_name = $filename_parts[0];
            $module_info = Yaml::parseFile($path);
            $semantic_version = self::getSemanticVersion($module_info['version']);
            if ($semantic_version === false) {
                $invalid_versions[] = $machine_name;
            } else {
                $projects[$machine_name] = $semantic_version;
            }
        }

        if (!empty($invalid_versions)) {
            throw new \Exception("The following projects contain invalid versions: " . implode(', ', $invalid_versions));
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
