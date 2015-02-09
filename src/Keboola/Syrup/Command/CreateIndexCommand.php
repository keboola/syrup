<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 22/07/14
 * Time: 15:22
 */

namespace Keboola\Syrup\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Keboola\Syrup\Exception\ApplicationException;
use Keboola\Syrup\Job\Metadata\JobManager;

class CreateIndexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:create-index')
            ->setDescription('Create new elasticsearch index')
            ->addOption('default', '-d', InputOption::VALUE_NONE, 'If set, default mapping wil be used, use this option
                also when running command from syrup-component-bundle space.')
            ->addOption('no-mapping', null, InputOption::VALUE_NONE, 'Creates index without any mappings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = null;
        $mappings = null;

        if (!$input->getOption('no-mapping')) {

            $mappingsPath = realpath(__DIR__ . '/../Resources/views/Elasticsearch/');

            if (!is_dir($mappingsPath)) {
                throw new ApplicationException("Unable to access directory 'Resources/views/Elasticsearch'");
            }

            $this->getContainer()->get('twig.loader')->addPath($mappingsPath, $namespace = '__main__');

            /** @var TwigEngine $templating */
            $templating = $this->getContainer()->get('templating');
            $mappingJson = $templating->render('mapping.json.twig');
            $mapping = json_decode($mappingJson, true);

            if (null == $mapping) {
                throw new ApplicationException("Error in mapping, check your mapping syntax");
            }

            $settings = $mapping['settings'];
            $mappings = $mapping['mappings'];
        }

        /** @var JobManager $jobManager */
        $jobManager = $this->getContainer()->get('syrup.job_manager');

        // try put mapping first
        try {
            $indexName = $jobManager->putMappings($mappings);
            echo "Mapping $indexName updated successfuly" . PHP_EOL;
        } catch (\Exception $e) {
            echo "Can't updated mapping: " . $e->getMessage() . PHP_EOL;

            $index = $jobManager->createIndex($settings, $mappings);
            echo "Created new index '" . $index ."'" . PHP_EOL;
        }

    }
}
