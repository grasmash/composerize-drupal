<?php

namespace Grasmash\ComposerConverter\Utility;

class ComposerJsonManipulator {


  /**
   * @param object $composer_json1
   * @param object $composer_json2
   *
   * @return mixed
   */
  public static function merge($composer_json1, $composer_json2) {
    $composer_json1_string = json_encode($composer_json1);
    $composer_json1_array = json_decode($composer_json1_string, TRUE);
    $composer_json2_string = json_encode($composer_json2);
    $composer_json2_array = json_decode($composer_json2_string, TRUE);

    $merged_array = ArrayManipulator::arrayMergeRecursiveDistinct($composer_json1_array, $composer_json2_array);
    $merged_composer_json_string = json_encode($merged_array);
    $merged_composer_json = json_decode($merged_composer_json_string);

    // Ensure that require and require-dev are objects and not arrays.
    if (property_exists($merged_composer_json, 'require') && is_array($merged_composer_json->{'require'})) {
      $merged_composer_json->{'require'} = (object) $merged_composer_json->{'require'};
    }
    if (property_exists($merged_composer_json, 'require-dev')&& is_array($merged_composer_json->{'require-dev'})) {
      $merged_composer_json->{'require-dev'} = (object) $merged_composer_json->{'require-dev'};
    }

    return $merged_composer_json;
  }

  public static function processPaths(&$template_composer_json, $drupal_root) {
    foreach ($template_composer_json->extra->{'installer-paths'} as $path => $types) {
      $processed_path = str_replace('[drupal-root]', $drupal_root, $path);
      if ($processed_path != $path) {
        unset($template_composer_json->extra->{'installer-paths'}->{$path});
        $template_composer_json->extra->{'installer-paths'}->{$processed_path} = $types;
      }
    }

    foreach ($template_composer_json->extra->{'merge-plugin'}->{'include'} as $key => $path) {
      $processed_path = str_replace('[drupal-root]', $drupal_root, $path);
      if ($processed_path != $path) {
        $template_composer_json->extra->{'merge-plugin'}->{'include'}[$key] = $processed_path;
      }
    }
  }

  public static function writeObjectToJsonFile($object, $filename) {
    file_put_contents($filename, json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

}