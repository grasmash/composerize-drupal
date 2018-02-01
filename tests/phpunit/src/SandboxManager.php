<?php

namespace Grasmash\ComposerConverter\Tests;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class SandboxManager
{
    protected $drupalVersion;

  /** @var string */
    protected $composerizeDrupalPath;

  /** @var Filesystem */
    protected $fs;

    public function __construct($drupal_version)
    {
        $this->fs = new Filesystem();
        $this->composerizeDrupalPath = dirname(dirname(dirname(__DIR__)));
        $this->drupalVersion = $drupal_version;
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
        $this->dowloadAndCopyDrupalCore($this->drupalVersion, $tmp, $sandbox);
        $this->downloadAndCopyCtools($tmp, $sandbox);

        chdir($sandbox);
        $process = new Process(
            'git init' .
            ' && git add -A' .
            ' && git commit -m "Initial commit."'
        );
        $process->run();

        return $sandbox;
    }

    /**
     * @param $tarball_filepath
     * @param $tarball_filename
     * @param $tar_filepath
     * @param $untarred_dirpath
     */
    protected function downloadProjectFromDrupalOrg(
        $tarball_filepath,
        $tarball_filename,
        $tar_filepath,
        $untarred_dirpath
    ) {
        if (!file_exists($tarball_filepath)) {
            file_put_contents(
                $tarball_filepath,
                fopen(
                    "https://ftp.drupal.org/files/projects/$tarball_filename",
                    'r'
                )
            );
        }
        if (!file_exists($untarred_dirpath)) {
            $this->fs->remove([
                $tar_filepath,
                $untarred_dirpath,
            ]);
            $p = new \PharData($tarball_filepath);
            $p->decompress();
            $phar = new \PharData($tar_filepath);
            $phar->extractTo($untarred_dirpath);
        }
    }

    /**
     * @param $drupal_version
     * @param $tmp
     * @param $sandbox
     *
     * @return array
     */
    protected function dowloadAndCopyDrupalCore(
        $drupal_version,
        $tmp,
        $sandbox
    ) {
        $drupal_project_string = "drupal-$drupal_version";
        $tarball_filename = "$drupal_project_string.tar.gz";
        $tarball_filepath = "$tmp/$tarball_filename";
        $tar_filepath = "$tmp/drupal-8.tar";
        $untarred_dirpath = "$tmp/drupal-8";

        if (!file_exists("$untarred_dirpath/$drupal_project_string")) {
            $this->downloadProjectFromDrupalOrg(
                $tarball_filepath,
                $tarball_filename,
                $tar_filepath,
                $untarred_dirpath
            );
        }
        $this->fs->mirror(
            "$untarred_dirpath/$drupal_project_string",
            $sandbox . "/docroot"
        );
    }

    /**
     * @param $tmp
     * @param $sandbox
     */
    protected function downloadAndCopyCtools($tmp, $sandbox)
    {
        $ctools_version = '8.x-3.0';
        $tarball_filename = "ctools-$ctools_version.tar.gz";
        $tarball_filepath = "$tmp/$tarball_filename";
        $tar_filepath = "$tmp/ctools-8.tar";
        $untarred_dirpath = "$tmp/ctools-8";
        if (!file_exists("$untarred_dirpath/$ctools_version")) {
            $this->downloadProjectFromDrupalOrg(
                $tarball_filepath,
                $tarball_filename,
                $tar_filepath,
                $untarred_dirpath
            );
        }
        $this->fs->mirror(
            "$untarred_dirpath/ctools",
            $sandbox . "/docroot/modules/contrib/ctools"
        );
    }
}
