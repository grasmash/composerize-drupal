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

    // @todo Test --drupal-root option.

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
        $this->assertObjectHasAttribute('drupal/ctools', $composer_json->require);
        $this->assertEquals("^3.0.0", $composer_json->require->{'drupal/ctools'});
    }

    /**
     * Test command when Drupal is not is a subdirectory like web or docroot.
     */
    public function testNoSubdirectory() {
        $this->sandbox = $this->sandbox . "/docroot";
        chdir($this->sandbox);
        $args = [
            '--composer-root' => '.',
        ];
        $options = [ 'interactive' => false ];
        $this->commandTester->execute($args, $options);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertNotContains('[drupal-root]', file_get_contents($this->sandbox . "/composer.json"));

        $composer_json = json_decode(file_get_contents($this->sandbox . "/composer.json"));

        // Modules existing in codebase were added to composer.json.
        $this->assertObjectHasAttribute('drupal/ctools', $composer_json->require);
        $this->assertEquals("^3.0.0", $composer_json->require->{'drupal/ctools'});
    }

    /**
     * Test command when Drupal is in a subdirectory other than docroot.
     */
    public function testWeirdSubdirectory() {
        $this->fs->rename($this->sandbox . "/docroot", $this->sandbox . "/drupal8");
        $args = [
            '--drupal-root' => 'drupal8',
        ];
        $options = [ 'interactive' => false ];
        $this->commandTester->execute($args, $options);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertNotContains('[drupal-root]', file_get_contents($this->sandbox . "/composer.json"));

        $composer_json = json_decode(file_get_contents($this->sandbox . "/composer.json"));

        // Modules existing in codebase were added to composer.json.
        $this->assertObjectHasAttribute('drupal/ctools', $composer_json->require);
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
        $process->setTimeout(null);
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

        $this->assertFileExists($this->sandbox . "/composer.json");
    }
}
