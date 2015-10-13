<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 02/07/14
 * Time: 14:53
 */

namespace Keboola\Syrup\Service\Queue;

use Aws\Sqs\SqsClient;
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
        if ($name == 'kill') {
            $hostnameArr = explode('.', gethostname());
            $name = 'syrup_kill_' . array_shift($hostnameArr);
        }

        $sql = "SELECT access_key, secret_key, region, url FROM {$this->dbTable} WHERE id = '{$name}'";
        $queueConfig = $this->db->query($sql)->fetch();

        if (!$queueConfig) {
            throw new ApplicationException('No queue configuration found in DB.');
        }

        return new QueueService($queueConfig, $this->componentName);
    }

    public function create($name, $region = 'us-east-1', $key = null, $secret = null)
    {
        $data = [
            'region' => $region,
            'version' => '2012-11-05'
        ];

        if ($key != null && $secret != null) {
            $data['key'] = $key;
            $data['secret'] = $secret;
        }

        $sqsClient = new SqsClient($data);

        $sqsQueue = $sqsClient->createQueue([
            'QueueName' => $name
        ]);

        return $sqsQueue;
    }
}
