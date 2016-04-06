<?php
/**
 * RegisterBundlesCommand.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 6.6.13
 */

namespace Keboola\Syrup\Command;

use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class RegisterBundlesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:register-bundles')
            ->setDescription('This will take components from parameters.yml and register them in AppKernel.php')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $components = $this->getContainer()->getParameter('components');

        $kernelManipulator = new KernelManipulator($kernel);

        foreach ($components as $component) {
            $classArr = explode('\\', $component['class']);
            $bundleName = $classArr[0] . $classArr[1];
            $bundleClassName = $classArr[0] . '\\' . $classArr[1] . '\\' . $bundleName;

            if (!class_exists($bundleClassName)) {
                $bundleName = $classArr[0] . $classArr[1] . $classArr[2];
                $bundleClassName = $classArr[0] . '\\' . $classArr[1] . '\\' . $classArr[2] . '\\' . $bundleName;

                if (!class_exists($bundleClassName)) {
                    throw new \Exception("Bundle class not found");
                }
            }

            echo "Checking if " . $bundleName . " is in Kernel" . PHP_EOL;

            try {
                $kernel->getBundle($bundleName);
            } catch (\InvalidArgumentException $e) {
                // register bundle to kernel

                echo "Registering bundle " . $bundleName . PHP_EOL;

                try {
                    $kernelManipulator->addBundle($bundleClassName);
                } catch (\RuntimeException $e) {
                    return array(
                        sprintf('Bundle <comment>%s</comment> is already defined in <comment>AppKernel::registerBundles()</comment>.', $bundleClassName),
                        '',
                    );
                }

                $output->writeln($bundleName . " succesfully registered to Kernel." . PHP_EOL);
            }
        }
    }
}
