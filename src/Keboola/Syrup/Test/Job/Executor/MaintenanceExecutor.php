<?php
/**
 * @package syrup
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */
namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Exception\MaintenanceException;
use Keboola\Syrup\Job\Metadata\Job;

class MaintenanceExecutor extends \Keboola\Syrup\Job\Executor
{
    public function execute(Job $job)
    {
        parent::execute($job);

        throw new MaintenanceException('maintenance');
    }
}
