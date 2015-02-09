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

    /**
     * @param Job $job
     * @return array|Job
     */
    public function execute(Job $job);
}
