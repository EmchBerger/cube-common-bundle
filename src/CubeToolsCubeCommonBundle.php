<?php

namespace CubeTools\CubeCommonBundle;

use CubeTools\CubeCommonDevelop\SymfonyCommands;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;

class CubeToolsCubeCommonBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        parent::registerCommands($application);

        if ($this->container && $this->container->getParameter('kernel.debug') &&
            class_exists(SymfonyCommands::class)
        ) {
            SymfonyCommands::addCcdCommands($application);
        }
    }
}
