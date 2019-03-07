<?php

namespace Grasmash\ComposerConverter\Tests;

use Composer\IO\ConsoleIO;
use Grasmash\ComposerConverter\Composer\ComposerizeDrupalCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestableComposerizeDrupalCommand extends ComposerizeDrupalCommand
{

    /**
     * Set io on the test application.
     *
     * CommandTester sets $this->input and $this->output directly, but the
     * Composer application does not use those properties directly. Instead,
     * it uses $this->io as a proxy to input and output.
     *
     * We use the tested command's initialize method to set $this->io on the
     * Composer application. We need to use our own Application class to do
     * this, since Composer does not provide a setIO() method.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $helperSet = new HelperSet([ new QuestionHelper() ]);

        /**
         * @var \Grasmash\ComposerConverter\Tests\Application $application
         */
        $application = $this->getApplication();
        $application->setIo(new ConsoleIO($input, $output, $helperSet));
    }
}
