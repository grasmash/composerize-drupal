<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

abstract class TestBase extends \PHPUnit_Framework_TestCase
{

  /** @var Filesystem */
    protected $fs;

  /** @var string */
    protected $composerizeDrupalPath;

  /** @var string */
    protected $sandbox;

  /** @var \Grasmash\ComposerConverter\Tests\SandboxManager */
    protected $sandboxManager;

    protected $drupalVersion;

  /**
   * {@inheritdoc}
   *
   * @see https://symfony.com/doc/current/console.html#testing-commands
   */
    public function setUp()
    {
        parent::setUp();
        $this->drupalVersion = '8.6.4';
        $this->fs = new Filesystem();
        $this->composerizeDrupalPath = dirname(dirname(dirname(__DIR__)));
        $this->sandboxManager = new SandboxManager($this->drupalVersion);
    }
}
