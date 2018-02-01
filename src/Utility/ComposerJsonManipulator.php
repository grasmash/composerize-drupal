<?php

namespace Grasmash\ComposerConverter\Utility;

class ComposerJsonManipulator
{

    public static function processPaths(&$template_composer_json, $drupal_root)
    {
        if ($drupal_root == '.') {
            $replacement = '';
        } else {
            $replacement = "$drupal_root/";
        }
        foreach ($template_composer_json->extra->{'installer-paths'} as $path => $types) {
            $processed_path = str_replace('[drupal-root]/', $replacement, $path);
            if ($processed_path != $path) {
                unset($template_composer_json->extra->{'installer-paths'}->{$path});
                $template_composer_json->extra->{'installer-paths'}->{$processed_path} = $types;
            }
        }

        foreach ($template_composer_json->extra->{'merge-plugin'}->{'include'} as $key => $path) {
            $processed_path = str_replace('[drupal-root]/', $replacement, $path);
            if ($processed_path != $path) {
                $template_composer_json->extra->{'merge-plugin'}->{'include'}[$key] = $processed_path;
            }
        }
    }

    public static function writeObjectToJsonFile($object, $filename)
    {
        file_put_contents($filename, json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
