<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 18/03/15
 * Time: 13:40
 */

namespace Keboola\Syrup\Job;

use Keboola\Syrup\Job\Metadata\Job;
use Keboola\Syrup\Service\StorageApi\StorageApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ExecutorFactory
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(Job $job)
    {
        /** @var StorageApiService $storageApiService */
        $storageApiService = $this->container->get('syrup.storage_api');

        $jobExecutorName = str_replace('-', '_', $job->getComponent()) . '.job_executor';

        /** @var ExecutorInterface $jobExecutor */

        try {
            $jobExecutor = $this->container->get($jobExecutorName);
        } catch (ServiceNotFoundException $e) {
            $jobExecutor = $this->container->get('syrup.job_executor');
        }

        $jobExecutor->setStorageApi($storageApiService->getClient());
        $jobExecutor->setJob($job);

        // register signal handler for SIGTERM if not on Win
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            pcntl_signal(SIGTERM, [$jobExecutor, 'onTerminate']); 
        }

        return $jobExecutor;
    }
}
