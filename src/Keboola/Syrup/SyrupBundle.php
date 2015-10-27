<?php
namespace Keboola\Syrup;

use Keboola\Syrup\DependencyInjection\AddEncryptorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SyrupBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddEncryptorPass());
    }
}
