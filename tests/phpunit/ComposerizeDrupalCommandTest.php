<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class ComposerizeDrupalCommandTest extends CommandTestBase
{

    /**
     * {@inheritdoc}
     *
     * @see https://symfony.com/doc/current/console.html#testing-commands
     */
    public function setUp()
    {
        parent::setUp();
        $this->sandbox = $this->sandboxManager->makeSandbox();
        $this->application->add(new TestableComposerizeDrupalCommand());
        $this->command = $this->application->find('composerize-drupal');
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Tests that composer.json contents are valid.
     */
    public function testComposerJsonIsValid()
    {
        $args = [];
        $options = [ 'interactive' => false ];
        $this->commandTester->execute($args, $options);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertNotContains('[drupal-root]', file_get_contents($this->sandbox . "/composer.json"));

        $composer_json = json_decode(file_get_contents($this->sandbox . "/composer.json"));

        // Modules existing in codebase were added to composer.json.
        $this->assertContains('drupal/ctools', $composer_json->require);
        $this->assertEquals("^3.0.0", $composer_json->require->{'drupal/ctools'});
    }
    /**
     * Tests modules can be downloaded from Drupal.org.
     */
    public function testDrupalEndpoint()
    {
        $args = [];
        $options = [ 'interactive' => false ];
        $this->commandTester->execute($args, $options);
        $process = new Process('composer require drupal/token:1.1.0');
        $process->setTimeout(NULL);
        $process->run();
        $this->assertEquals(0, $process->getExitCode());
        $this->assertFileExists($this->sandbox . "/docroot/modules/contrib/token");
    }

    /**
     * Tests that a composer.json file is created if none exists.
     */
    public function testComposerJsonIsCreated()
    {
        $this->fs->remove($this->sandbox . "/composer.json");
        $args = [];
        $options = [ 'interactive' => false ];
        $this->commandTester->execute($args, $options);

        $this->assertFileExists($this->composerizeDrupalPath . "/composer.json");
    }


}
