<?php

namespace Grasmash\ComposerConverter\Composer;

use Composer\Semver\Semver;
use Composer\Util\ProcessExecutor;
use DrupalFinder\DrupalFinder;
use Grasmash\ComposerConverter\Utility\ComposerJsonManipulator;
use Grasmash\ComposerConverter\Utility\DrupalInspector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ComposerizeDrupalCommand extends BaseCommand
{

    /** @var InputInterface */
    protected $input;
    protected $baseDir;
    protected $composerConverterDir;
    protected $templateComposerJson;
    protected $rootComposerJsonPath;
    protected $drupalRoot;
    protected $drupalRootRelative;
    protected $drupalCoreVersion;
    /** @var Filesystem */
    protected $fs;

    public function configure()
    {
        $this->setName('composerize-drupal');
        $this->setDescription("Convert a non-Composer managed Drupal application into a Composer-managed application.");
        $this->addOption('composer-root', null, InputOption::VALUE_REQUIRED, 'The relative path to the directory that should contain composer.json.');
        $this->addOption('drupal-root', null, InputOption::VALUE_REQUIRED, 'The relative path to the Drupal root directory.');
        $this->addOption('exact-versions', null, InputOption::VALUE_NONE, 'Use exact version constraints rather than the recommended caret operator.');
        $this->addOption('no-update', null, InputOption::VALUE_NONE, 'Prevent "composer update" being run after file generation.');
        $this->addOption('no-gitignore', null, InputOption::VALUE_NONE, 'Prevent root .gitignore file from being modified.');
        $this->addUsage('--composer-root=. --drupal-root=./docroot');
        $this->addUsage('--composer-root=. --drupal-root=./web');
        $this->addUsage('--composer-root=. --drupal-root=.');
        $this->addUsage('--exact-versions --no-update --no-gitignore');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->fs = new Filesystem();
        $this->setDirectories($input);
        $this->drupalCoreVersion = $this->determineDrupalCoreVersion();
        $this->removeAllComposerFiles();
        $this->createNewComposerJson();
        $this->addRequirementsToComposerJson();
        if (!$this->input->getOption('no-gitignore')) {
            $this->mergeTemplateGitignore();
        }

        $exit_code = 0;
        if (!$input->getOption('no-update')) {
            $this->getIO()->write("Executing <comment>composer update</comment>...");
            $exit_code = $this->executeComposerUpdate();
        } else {
            $this->getIO()->write("Execute <comment>composer update</comment> to install dependencies.");
        }

        if (!$exit_code) {
            $this->printPostScript();
        }

        return $exit_code;
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

    protected function createNewComposerJson()
    {
        ComposerJsonManipulator::writeObjectToJsonFile(
            $this->getTemplateComposerJson(),
            $this->rootComposerJsonPath
        );
        $this->getIO()->write("<info>Created composer.json</info>");
    }

    protected function addRequirementsToComposerJson()
    {
        $root_composer_json = $this->loadRootComposerJson();
        $projects = $this->findContribProjects($root_composer_json);
        $this->requireContribProjects($root_composer_json, $projects);
        $this->requireDrupalCore($root_composer_json);
        $this->addPatches($projects, $root_composer_json);

        ComposerJsonManipulator::writeObjectToJsonFile(
            $root_composer_json,
            $this->rootComposerJsonPath
        );
    }

    /**
     * @return mixed|string
     * @throws \Exception
     */
    protected function determineDrupalCoreVersion()
    {
        if (file_exists($this->drupalRoot . "/core/lib/Drupal.php")) {
            $bootstrap =  file_get_contents($this->drupalRoot . "/core/lib/Drupal.php");
            $core_version = DrupalInspector::determineDrupalCoreVersionFromDrupalPhp($bootstrap);

            if (!Semver::satisfiedBy([$core_version], "*")) {
                throw new \Exception("Drupal core version $core_version is invalid.");
            }

            return $core_version;
        }
        if (!isset($this->drupalCoreVersion)) {
            throw new \Exception("Unable to determine Drupal core version.");
        }
    }

    /**
     * @param $root_composer_json
     * @param $projects
     */
    protected function requireContribProjects($root_composer_json, $projects)
    {
        foreach ($projects as $project_name => $project) {
            $package_name = "drupal/$project_name";
            $version_constraint = DrupalInspector::getVersionConstraint($project['version'], $this->input->getOption('exact-versions'));
            $root_composer_json->require->{$package_name} = $version_constraint;

            if ($version_constraint == "*") {
                $this->getIO()->write("<comment>Could not determine correct version for project $package_name. Added to requirements without constraint.</comment>");
            } else {
                $this->getIO()->write("<info>Added $package_name with constraint $version_constraint to requirements.</info>");
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     */
    protected function setDirectories(InputInterface $input)
    {
        $this->composerConverterDir = dirname(dirname(__DIR__));
        $drupalFinder = new DrupalFinder();
        $this->determineDrupalRoot($input, $drupalFinder);
        $this->determineComposerRoot($input, $drupalFinder);
        $this->drupalRootRelative = trim($this->fs->makePathRelative(
            $this->drupalRoot,
            $this->baseDir
        ), '/');
        $this->rootComposerJsonPath = $this->baseDir . "/composer.json";
    }

    /**
     * @return int
     */
    protected function executeComposerUpdate()
    {
        $io = $this->getIO();
        $executor = new ProcessExecutor($io);
        $output_callback = function ($type, $buffer) use ($io) {
            $io->write($buffer, false);
        };
        return $executor->execute('composer update --no-interaction', $output_callback, $this->baseDir);
    }

    /**
     *
     */
    protected function mergeTemplateGitignore()
    {
        $template_gitignore = file($this->composerConverterDir . "/template.gitignore");
        $gitignore_entries = [];
        foreach ($template_gitignore as $key => $line) {
            $gitignore_entries[] = str_replace(
                '[drupal-root]',
                $this->drupalRootRelative,
                $line
            );
        }
        $root_gitignore_path = $this->getBaseDir() . "/.gitignore";
        $verb = "modified";
        if (!file_exists($root_gitignore_path)) {
            $verb = "created";
            $this->fs->touch($root_gitignore_path);
        }
        $root_gitignore = file($root_gitignore_path);
        foreach ($root_gitignore as $key => $line) {
            if ($key_to_remove = array_search($line, $gitignore_entries)) {
                unset($gitignore_entries[$key_to_remove]);
            }
        }
        $merged_gitignore = $root_gitignore + $gitignore_entries;
        file_put_contents(
            $root_gitignore_path,
            implode('', $merged_gitignore)
        );

        $this->getIO()->write("<info>$verb .gitignore. Composer dependencies will NOT be committed.</info>");
    }

    /**
     * @param $root_composer_json
     */
    protected function requireDrupalCore($root_composer_json)
    {
        $version_constraint = DrupalInspector::getVersionConstraint($this->drupalCoreVersion, $this->input->getOption('exact-versions'));
        $root_composer_json->require->{'drupal/core-recommended'} = $version_constraint;
        $this->getIO()
            ->write("<info>Added drupal/core-recommended $version_constraint to requirements.</info>");
        // Adding drupal/core-composer-scaffold with the same drupal core version.
        $root_composer_json->require->{'drupal/core-composer-scaffold'} = $version_constraint;
        $this->getIO()
            ->write("<info>Added drupal/core-composer-scaffold $version_constraint to requirements.</info>");
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param DrupalFinder $drupalFinder
     *
     * @throws \Exception
     */
    protected function determineComposerRoot(
        InputInterface $input,
        DrupalFinder $drupalFinder
    ) {
        if ($input->getOption('composer-root')) {
            if (!$this->fs->isAbsolutePath($input->getOption('composer-root'))) {
                $this->baseDir = getcwd() . "/" . $input->getOption('composer-root');
            } else {
                $this->baseDir = $input->getOption('composer-root');
            }
        } else {
            $this->baseDir = $drupalFinder->getComposerRoot();
            $confirm = $this->getIO()
                ->askConfirmation("<question>Assuming that composer.json should be generated at {$this->baseDir}. Is this correct?</question> ");
            if (!$confirm) {
                throw new \Exception("Please use --composer-root to specify the correct Composer root directory");
            }
        }
    }

    /**
     * @param InputInterface $input
     * @param DrupalFinder $drupalFinder
     *
     * @throws \Exception
     */
    protected function determineDrupalRoot(InputInterface $input, DrupalFinder $drupalFinder)
    {
        if (!$input->getOption('drupal-root')) {
            $common_drupal_root_subdirs = [
                'docroot',
                'web',
                'htdocs',
            ];
            $root = getcwd();
            foreach ($common_drupal_root_subdirs as $candidate) {
                if (file_exists("$root/$candidate")) {
                    $root = "$root/$candidate";
                    break;
                }
            }
        } else {
            $root = $input->getOption('drupal-root');
        }

        if ($drupalFinder->locateRoot($root)) {
            $this->drupalRoot = $drupalFinder->getDrupalRoot();
            if (!$this->fs->isAbsolutePath($root)) {
                $this->drupalRoot = getcwd() . "/$root";
            }
        } else {
            throw new \Exception("Unable to find Drupal root directory. Please change directories to a valid Drupal 8 application. Try specifying it with --drupal-root.");
        }
    }

    /**
     * Removes all composer.json and composer.lock files recursively.
     */
    protected function removeAllComposerFiles()
    {
        $finder = new Finder();
        $finder->in($this->baseDir)
            ->files()
            ->name('/(^composer\.(lock|json)$)|autoload.php/');
        $files = iterator_to_array($finder);
        $this->fs->remove($files);
    }

    protected function printPostScript()
    {
        $this->getIO()->write("<info>Completed composerization of Drupal!</info>");
        $this->getIO()->write("Please review relevant documentation on Drupal.org:");
        $this->getIO()->write("<comment>https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies</comment>");
        $this->getIO()->write("");
        $this->getIO()->write("These additional resources may also be helpful:");
        $this->getIO()->write("  * <comment>https://www.lullabot.com/articles/drupal-8-composer-best-practices</comment>");
    }

    /**
     * @param $root_composer_json
     *
     * @return array
     */
    protected function findContribProjects($root_composer_json)
    {
        $modules_contrib = DrupalInspector::findContribProjects(
            $this->drupalRoot,
            "modules/contrib",
            $root_composer_json
        );
        $modules = DrupalInspector::findContribProjects(
            $this->drupalRoot,
            "modules",
            $root_composer_json
        );
        $themes = DrupalInspector::findContribProjects(
            $this->drupalRoot,
            "themes/contrib",
            $root_composer_json
        );
        $profiles = DrupalInspector::findContribProjects(
            $this->drupalRoot,
            "profiles/contrib",
            $root_composer_json
        );
        $projects = array_merge($modules_contrib, $modules, $themes, $profiles);
        return $projects;
    }

    /**
     * @param $projects
     * @param $root_composer_json
     */
    protected function addPatches($projects, $root_composer_json)
    {
        $projects = DrupalInspector::findProjectPatches($projects);
        $patch_dir = $this->getBaseDir() . "/patches";
        $this->fs->mkdir($patch_dir);
        foreach ($projects as $project_name => $project) {
            if (array_key_exists('patches', $project)) {
                foreach ($project['patches'] as $key => $patch) {
                    $target_filename = $patch_dir . "/" . basename($patch);
                    $this->fs->copy($patch, $target_filename);
                    $relative_path = $this->fs->makePathRelative(
                        $target_filename,
                        $this->getBaseDir()
                    );
                    $relative_path = rtrim($relative_path, '/');
                    $root_composer_json->extra->patches["drupal/" . $project_name][$relative_path] = $relative_path;
                }
            }
        }
    }
}
