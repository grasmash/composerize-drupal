<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

abstract class CommandTestBase extends TestBase
{
    /**
     * @var \Grasmash\ComposerConverter\Tests\Application
     */
    protected $application;

    /**
     * @var \Grasmash\ComposerConverter\Tests\TestableComposerizeDrupalCommand $command
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    /**
     * {@inheritdoc}
     *
     * @see https://symfony.com/doc/current/console.html#testing-commands
     */
    public function setUp()
    {
        parent::setUp();
        $this->application = new Application();
    }
}
