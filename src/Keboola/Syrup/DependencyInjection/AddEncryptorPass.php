<?php

namespace Keboola\Syrup\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers encryptor wrappers.
 */
class AddEncryptorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $encryptorDefinition = $container->getDefinition('syrup.object_encryptor');
        foreach ($container->findTaggedServiceIds('syrup.encryption.wrapper') as $id => $tags) {
            $encryptorDefinition->addMethodCall('pushWrapper', [new Reference($id)]);
        }
    }
}
