<?php

namespace CubeTools\CubeCommonBundle;

use CubeTools\CubeCommonBundle\Security\VersatileFormLoginLdapFactory;
use CubeTools\CubeCommonDevelop\SymfonyCommands;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CubeToolsCubeCommonBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new VersatileFormLoginLdapFactory());
    }

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
