<?php

namespace Grasmash\ComposerConverter\Tests;

class Application extends \Composer\Console\Application
{
    public function setIo($io)
    {
        $this->io = $io;
    }
}
