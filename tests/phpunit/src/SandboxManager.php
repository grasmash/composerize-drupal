<?php

namespace Grasmash\ComposerConverter\Tests;

use Alchemy\Zippy\Zippy;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class SandboxManager
{
    protected $tmp;

    protected $drupalVersion = "8.6.0";

    /**
     * @param mixed $drupalVersion
     */
    public function setDrupalVersion($drupalVersion)
    {
        $this->drupalVersion = $drupalVersion;
    }

    /**
     * @return mixed
     */
    public function getDrupalVersion()
    {
        return $this->drupalVersion;
    }

    /**
     * @var string
     */
    protected $composerizeDrupalPath;

    /**
     * @var Filesystem
     */
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
        $this->tmp = getenv('COMPOSERIZE_DRUPAL_TMP') ?: sys_get_temp_dir();
        $sandbox = Path::canonicalize($this->tmp . "/composerize-drupal-sandbox");
        $this->deleteFolderRecursively($sandbox);
        $this->fs->mkdir([$sandbox]);
        $sandbox = realpath($sandbox);
        $sandbox_master = Path::canonicalize($this->composerizeDrupalPath . "/tests/fixtures/sandbox");
        $this->fs->mirror($sandbox_master, $sandbox);
        $this->downloadAndCopyDrupalCore($this->drupalVersion, $this->tmp, $sandbox);
        $this->downloadAndCopyCtools($this->tmp, $sandbox);
        // Create fake patch for ctools.
        $this->fs->touch($sandbox . "/docroot/modules/contrib/ctools/test.patch");

        chdir($sandbox);
        $process = new Process(
            'git init' .
            ' && git add -A' .
            ' && git commit -m "Initial commit."',
            null,
            null,
            null,
            60 * 5
        );
        $process->run();

        return $sandbox;
    }

    /**
     * A helper function to delete folder recursively.
     * See error in build https://travis-ci.com/github/rahulsonar-acquia/composerize-drupal/jobs/506595288
     *
     * @param $dir
     */
    private function deleteFolderRecursively($dir):void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                        $this->deleteFolderRecursively($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        $this->fs->remove($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            $this->fs->remove($dir);
        }
    }

    protected function downloadProjectFromDrupalOrg($project_string)
    {
        $targz_filename = "$project_string.tar.gz";
        $targz_filepath = "{$this->tmp}/$targz_filename";
        $tar_filepath = str_replace('.gz', '', $targz_filepath);
        $untarred_dirpath = str_replace('.tar', '', $tar_filepath);
        if (!file_exists($targz_filepath) || getenv('COMPOSERIZE_DRUPAL_NO_CACHE') == true) {
            file_put_contents(
                $targz_filepath,
                fopen(
                    "https://ftp.drupal.org/files/projects/$targz_filename",
                    'r'
                )
            );
        }
        if (!file_exists($untarred_dirpath)) {
            $zippy = Zippy::load();
            $archive = $zippy->open($targz_filepath);
            $archive->extract($this->tmp);
        }

        return $untarred_dirpath;
    }

    /**
     * @param $drupal_version
     * @param $tmp
     * @param $sandbox
     *
     * @return array
     */
    protected function downloadAndCopyDrupalCore(
        $drupal_version,
        $tmp,
        $sandbox
    ) {
        $drupal_project_string = "drupal-$drupal_version";
         $this->downloadProjectFromDrupalOrg($drupal_project_string);
        $this->fs->mirror(
            "{$this->tmp}/drupal-$drupal_version",
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
        $project_string = "ctools-$ctools_version";
        $this->downloadProjectFromDrupalOrg($project_string);
        $this->fs->mirror(
            "{$this->tmp}/ctools",
            $sandbox . "/docroot/modules/contrib/ctools"
        );
    }
}
