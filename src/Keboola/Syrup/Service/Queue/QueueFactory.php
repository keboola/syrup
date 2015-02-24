<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/07/14
 * Time: 14:53
 */

namespace Keboola\Syrup\Service\Queue;

use Doctrine\DBAL\Connection;
use Keboola\Syrup\Exception\ApplicationException;

class QueueFactory
{
    protected $db;

    protected $dbTable;

    protected $componentName;

    public function __construct(Connection $db, $queueParams, $componentName)
    {
        $this->db = $db;
        $this->dbTable = $queueParams['db_table'];
        $this->componentName = $componentName;
    }

    public function get($name = 'default')
    {
        $sql = "SELECT access_key, secret_key, region, url FROM {$this->dbTable} WHERE id = '{$name}'";
        $queueConfig = $this->db->query($sql)->fetch();

        if (!$queueConfig) {
            throw new ApplicationException('No queue configuration found in DB.');
        }

        return new QueueService($queueConfig, $this->componentName);
    }
}
