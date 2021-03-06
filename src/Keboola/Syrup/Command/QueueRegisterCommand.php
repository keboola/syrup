<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 09/09/14
 * Time: 11:54
 */

namespace Keboola\Syrup\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueRegisterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:queue:register')
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('access_key', InputArgument::REQUIRED)
            ->addArgument('secret_key', InputArgument::REQUIRED)
            ->addArgument('region', InputArgument::REQUIRED)
            ->addArgument('url', InputArgument::REQUIRED)
            ->setDescription('Store SQS queue attributes and credentials into Syrup DB.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = [
            'id' => $input->getArgument('id'),
            'access_key' => $input->getArgument('access_key'),
            'secret_key' => $input->getArgument('secret_key'),
            'region' => $input->getArgument('region'),
            'url' => $input->getArgument('url')
        ];

        /** @var Connection $conn */
        $conn = $this->getContainer()->get('doctrine.dbal.syrup_connection');

        unset($data['command']);

        $conn->insert('queues', $data);
    }
}
