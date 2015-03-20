<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 06/06/14
 * Time: 14:59
 */

namespace Keboola\Syrup\Job;

use Keboola\StorageApi\Client;
use Keboola\Syrup\Job\Metadata\Job;

interface ExecutorInterface
{
    public function setStorageApi(Client $sapi);

    public function setJob(Job $job);

    /**
     * @param Job $job DEPRECATED - parameter $job will be removed in next release
     *                              in favor of setting $job as class member in ExecutorFactory
     * @return array|Job
     */
    public function execute(Job $job);

    public function cleanup();
}
