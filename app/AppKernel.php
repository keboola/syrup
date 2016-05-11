<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Yaml\Parser;

class AppKernel extends \Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new \Keboola\Syrup\SyrupBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new \Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new \Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        $components = $this->getComponents();
        if (count($components)) {
            foreach ($components as $component) {
                if (isset($component['bundle'])) {
                    $bundleClassName = $component['bundle'];
                    $bundles[] = new $bundleClassName;
                }
            }
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getComponents()
    {
        $yaml = new Parser();

        $components = null;
        try {
            $parameters = $yaml->parse(file_get_contents($this->getRootDir().'/config/parameters.yml'));
            $components = $parameters['parameters']['components'];
        } catch (\Exception $e) {
            throw new \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException($e->getMessage());
        }

        return $components;
    }
}
