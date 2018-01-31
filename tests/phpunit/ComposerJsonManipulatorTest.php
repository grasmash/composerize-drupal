<?php

namespace Grasmash\ComposerConverter\Tests;

use Grasmash\ComposerConverter\Utility\ComposerJsonManipulator;

/**
 * Tests the ComposerJsonManipulator class.
 */
class ComposerJsonManipulatorTest extends \PHPUnit_Framework_TestCase
{

  /**
   * Tests ComposerJsonManipulator::merge().
   */
    public function testMerge()
    {
        $composer_json1_path = dirname(__DIR__) . "/fixtures/sandbox/composer.json";
        $composer_json1 = json_decode(file_get_contents($composer_json1_path));
        $composer_json2_path = dirname(dirname(__DIR__)) . "/template.composer.json";
        $composer_json2 = json_decode(file_get_contents($composer_json2_path));
        $merged = ComposerJsonManipulator::merge($composer_json1, $composer_json2);

        $this->assertObjectHasAttribute('repositories', $merged);
        $this->assertObjectHasAttribute('composerize-drupal', $merged->repositories);
        $this->assertObjectHasAttribute('drupal', $merged->repositories);
        $this->assertObjectHasAttribute('asset-packagist', $merged->repositories);
        $this->assertObjectHasAttribute('extra', $merged);
        $this->assertObjectHasAttribute('installer-paths', $merged->extra);
        $this->assertObjectHasAttribute('[drupal-root]/core', $merged->extra->{'installer-paths'});
        $this->assertContains('type:drupal-core', $merged->extra->{'installer-paths'}->{'[drupal-root]/core'});
        $this->assertArrayHasKey(0, $merged->extra->{'installer-paths'}->{'[drupal-root]/core'});
    }
}
