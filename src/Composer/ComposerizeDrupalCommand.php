<?php

namespace Grasmash\ComposerConverter\Composer;

use Grasmash\ComposerConverter\Utility\ArrayManipulator;
use Grasmash\ComposerConverter\Utility\ComposerJsonManipulator;
use Grasmash\ComposerConverter\Utility\DrupalInspector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Symfony\Component\Filesystem\Filesystem;

class ComposerizeDrupalCommand extends BaseCommand
{

    protected $baseDir;
    protected $composerConverterDir;
    protected $templateComposerJson;
    protected $rootComposerJsonPath;
    protected $drupalRoot;
    protected $drupalRootRelative;
    protected $fs;

    public function configure()
    {
        $this->setName('composerize-drupal');
        $this->setDescription("Convert a non-Composer managed Drupal application into a Composer-managed application.");

      // @todo add --drupal-root param.
      // @todo add --exact-versions param.
    }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->composerConverterDir = dirname(dirname(__DIR__));
        $base_dir = $this->determineBaseDir();
        $this->setBaseDir($base_dir);
        // @todo Allow this to be different.
        $this->drupalRoot = $base_dir . "/docroot";
        $this->fs = new Filesystem();
        $this->drupalRootRelative = $this->fs->makePathRelative($this->drupalRoot, $this->baseDir);
        $this->rootComposerJsonPath = $this->baseDir . "/composer.json";

        if (!file_exists($this->rootComposerJsonPath)) {
            $this->createNewComposerJson();
        } else {
            $this->mergeTemplateIntoRootComposerJson();
        }

        $this->addDrupalModulesToComposerJson();

      // @todo Add to .gitignore.
    }

  /**
   * @return mixed
   */
    public function getTemplateComposerJson()
    {
        if (!isset($this->templateComposerJson)) {
            $this->templateComposerJson = $this->loadTemplateComposerJson();
        }

        return $this->templateComposerJson;
    }


    protected function determineBaseDir()
    {
        try {
            $composer = $this->getComposer(false);
        } catch (\Exception $e) {
        }
        if (isset($composer)) {
            $composer_json = $this->getComposer(false)
            ->getConfig()
            ->getConfigSource()
            ->getName();
            $base_dir = dirname($composer_json);
        } else {
            $base_dir = getcwd();
        }

        return $base_dir;
    }

  /**
   * @return mixed
   */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

  /**
   * @param mixed $baseDir
   */
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
    }

  /**
   * @return mixed
   */
    protected function loadTemplateComposerJson()
    {
        $template_composer_json = json_decode(file_get_contents($this->composerConverterDir . "/template.composer.json"));
        ComposerJsonManipulator::processPaths($template_composer_json, $this->drupalRootRelative);

        return $template_composer_json;
    }

    protected function loadRootComposerJson()
    {
        return json_decode(file_get_contents($this->rootComposerJsonPath));
    }

    protected function mergeTemplateIntoRootComposerJson()
    {
        $root_composer_json = $this->loadRootComposerJson();
        $template_composer_json = $this->getTemplateComposerJson();
        $merged_composer_json = ComposerJsonManipulator::merge(
            $root_composer_json,
            $template_composer_json
        );
        ComposerJsonManipulator::writeObjectToJsonFile($merged_composer_json, $this->rootComposerJsonPath);
    }

    protected function createNewComposerJson()
    {
        ComposerJsonManipulator::writeObjectToJsonFile(
            $this->getTemplateComposerJson(),
            $this->rootComposerJsonPath
        );
    }

    protected function addDrupalModulesToComposerJson()
    {
        $modules = DrupalInspector::findModules($this->drupalRoot);
        $root_composer_json = $this->loadRootComposerJson();
        foreach ($modules as $module => $version) {
            $package_name = "drupal/$module";
            $version_constraint = "^" . $version;
            $root_composer_json->require->{$package_name} = $version_constraint;
        }
        ComposerJsonManipulator::writeObjectToJsonFile(
            $root_composer_json,
            $this->rootComposerJsonPath
        );
    }
}
