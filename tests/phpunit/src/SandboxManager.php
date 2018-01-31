<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class SandboxManager
{

  /** @var string */
    protected $composerizeDrupalPath;

  /** @var Filesystem */
    protected $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
        $this->composerizeDrupalPath = dirname(dirname(dirname(__DIR__)));
    }

  /**
   * Destroy and re-create sandbox directory for testing.
   *
   * Sandbox is a mirror of tests/fixtures/sandbox, located in a temp dir.
   *
   * @return bool|string
   */
    public function makeSandbox()
    {
        $tmp = getenv('COMPOSERIZE_DRUPAL_TMP') ?: sys_get_temp_dir();
        $sandbox = Path::canonicalize($tmp . "/composerize-drupal-sandbox");
        $this->fs->remove([$sandbox]);
        $this->fs->mkdir([$sandbox]);
        $sandbox = realpath($sandbox);
        $sandbox_master = Path::canonicalize($this->composerizeDrupalPath . "/tests/fixtures/sandbox");
        $this->fs->mirror($sandbox_master, $sandbox);
        $composer_json = json_decode(file_get_contents($sandbox . "/composer.json"));
        $composer_json->repositories->{'composerize-drupal'}->url = $this->composerizeDrupalPath;
        $this->fs->dumpFile($sandbox . "/composer.json", json_encode($composer_json));
        chdir($sandbox);
        $process = new Process(
            'composer install --prefer-dist --no-progress --no-suggest --optimize-autoloader' .
            ' && git init' .
            ' && git add -A' .
            ' && git commit -m "Initial commit."'
        );
        $process->run();

        return $sandbox;
    }
}
