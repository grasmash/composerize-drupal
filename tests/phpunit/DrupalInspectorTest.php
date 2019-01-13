<?php

namespace Grasmash\ComposerConverter\Tests;

use Grasmash\ComposerConverter\Utility\ComposerJsonManipulator;
use Grasmash\ComposerConverter\Utility\DrupalInspector;
use Symfony\Component\Process\Process;

/**
 * Tests the DrupalInspector class.
 */
class DrupalInspectorTest extends TestBase
{

  /**
   * Tests DrupalInspector::findContribProjects().
   */
    public function testFindModules()
    {
        $this->sandbox = $this->sandboxManager->makeSandbox();
        $composer_json = json_decode(file_get_contents($this->sandbox . "/docroot/composer.json"));
        $modules = DrupalInspector::findContribProjects($this->sandbox . "/docroot", "modules/contrib", $composer_json);
        $this->assertArrayHasKey('ctools', $modules);
        $this->assertContains('3.0.0', $modules);
    }

  /**
   * @dataProvider getValueProvider
   */
    public function testGetSemanticVersion($drupal_version, $semantic_version)
    {
        $converted_version = DrupalInspector::getSemanticVersion($drupal_version);
        $this->assertEquals($semantic_version, $converted_version);
    }

  /**
   * Provides values to testArrayMergeNoDuplicates().
   *
   * @return array
   *   An array of values to test.
   */
    public function getValueProvider()
    {
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
