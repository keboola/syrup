<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 24/02/16
 * Time: 14:02
 */
namespace Keboola\Syrup\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('syrup:test:error')
            ->setDescription('Create an error')
            ->addArgument('error', InputArgument::REQUIRED, 'One of notice, warning, fatal, memory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $error = $input->getArgument('error');

        switch ($error) {
            case 'notice':
                trigger_error('This is NOTICE!', E_USER_NOTICE);
                break;
            case 'warning':
                trigger_error('This is WARNING!', E_USER_WARNING);
                break;
            case 'fatal':
                $foo = new Bar();
                break;
            case 'memory':
                $str = "something";
                while (true) {
                    $str .= "something else";
                }
                break;
            default:
                echo "You must specify one of 'notice | warning | fatal | memory'";
        }
    }
}
