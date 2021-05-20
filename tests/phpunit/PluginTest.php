<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Process\Process;

class PluginTest extends CommandTestBase
{

    /**
     * {@inheritdoc}
     *
     * @see https://symfony.com/doc/current/console.html#testing-commands
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tests that all expected commands are available in the application.
     *
     * @dataProvider getValueProvider
     */
    public function testComposerCommandsAvailable($expected)
    {
        $process = new Process('composer global config repositories.composerize-drupal path $(pwd)');
        $process->run();
        $process = new Process('composer global require grasmash/composerize-drupal "*@dev"');
        $process->run();
        // Code executed in `composer list` is not counted under code coverage.
        $process = new Process('COMPOSER_ALLOW_XDEBUG=1 composer list');
        $process->run();
        $this->assertContains($expected, $process->getOutput());
    }

    /**
     * Provides values to testComposerCommandsAvailable().
     *
     * @return array
     *   An array of values to test.
     */
    public function getValueProvider()
    {
        return [
            ['composerize-drupal'],
        ];
    }
}
