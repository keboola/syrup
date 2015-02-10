<?php
namespace Keboola\Syrup;

use Keboola\Syrup\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SyrupBundle extends Bundle
{

    public function getContainerExtension()
    {
        return new Extension();
    }
}
