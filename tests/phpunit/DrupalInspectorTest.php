<?php

namespace Grasmash\ComposerConverter\Tests;

use Grasmash\ComposerConverter\Utility\ComposerJsonManipulator;
use Grasmash\ComposerConverter\Utility\DrupalInspector;
use Symfony\Component\Process\Process;

/**
 * Tests the DrupalInspector class.
 */
class DrupalInspectorTest extends TestBase {

  /**
   *
   */
  protected function mergeTemplateIntoSandbox() {
    $composer_json1_path = $this->sandbox . "/composer.json";
    $composer_json1 = json_decode(file_get_contents($composer_json1_path));
    $composer_json2_path = dirname(dirname(__DIR__)) . "/template.composer.json";
    $composer_json2 = json_decode(file_get_contents($composer_json2_path));
    ComposerJsonManipulator::processPaths($composer_json2, "docroot");
    $merged = ComposerJsonManipulator::merge($composer_json1, $composer_json2);
    file_put_contents($composer_json1_path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

  /**
   * Tests DrupalInspector::findModules().
   */
  public function testFindModules() {
    $this->sandbox = $this->sandboxManager->makeSandbox();
    $this->mergeTemplateIntoSandbox();
    $process = new Process('composer require drupal/ctools:3.0');
    $process->setTimeout(NULL);
    $process->run();

    $modules = DrupalInspector::findModules($this->sandbox . "/docroot");
    $this->assertArrayHasKey('ctools', $modules);
    $this->assertContains('3.0.0', $modules);
  }

  /**
   * @dataProvider getValueProvider
   */
  public function testGetSemanticVersion($drupal_version, $semantic_version) {
    $converted_version = DrupalInspector::getSemanticVersion($drupal_version);
    $this->assertEquals($semantic_version, $converted_version);
  }

  /**
   * Provides values to testArrayMergeNoDuplicates().
   *
   * @return array
   *   An array of values to test.
   */
  public function getValueProvider() {
    return [
      ['3.0', '3.0.0'],
      ['1.x-dev', '1.x-dev'],
      ['3.12', '3.12.0'],
      ['3.0-alpha1', '3.0.0-alpha1'],
      ['3.12-beta2', '3.12.0-beta2'],
      ['4.0-rc12', '4.0.0-rc12'],
      ['0.1-rc2', '0.1.0-rc2'],
    ];
  }
}