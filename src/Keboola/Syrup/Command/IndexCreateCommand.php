<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 22/07/14
 * Time: 15:22
 */

namespace Keboola\Syrup\Command;

use Keboola\Syrup\Elasticsearch\ComponentIndex;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:index:create')
            ->setAliases(['syrup:create-index'])
            ->setDescription('Create new elasticsearch index')
            ->addOption('default', '-d', InputOption::VALUE_NONE, 'If set, default mapping wil be used, use this option
                also when running command from syrup-component-bundle space.')
            ->addOption('no-mapping', null, InputOption::VALUE_NONE, 'Creates index without any mappings.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = null;
        $mapping = null;

        if (!$input->getOption('no-mapping')) {
            $mapping = ComponentIndex::buildMapping($this->getContainer()->get('kernel')->getRootDir());

            $settings = $mapping['settings'];
            $mapping = $mapping['mappings'];
        }

        /** @var ComponentIndex $index */
        $index = $this->getContainer()->get('syrup.elasticsearch.current_component_index');

        // try put mapping first
        try {
            $indexName = $index->putMapping($mapping);
            echo "Mapping $indexName updated successfuly" . PHP_EOL;
        } catch (\Exception $e) {
            echo "Can't updated mapping: " . $e->getMessage() . PHP_EOL;

            $indexName = $index->createIndex($settings, $mapping);
            echo "Created new index '" . $indexName ."'" . PHP_EOL;
        }
    }
}
