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
        // Code executed in `composer list` is not counted under code coverage.
        $process = new Process('COMPOSER_ALLOW_XDEBUG=1 ./vendor/bin/composer list', $this->sandbox);
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
