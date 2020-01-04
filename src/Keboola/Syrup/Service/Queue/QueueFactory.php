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
use Keboola\Syrup\Utility\Utility;

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
            $name = Utility::generateKillQueueName(gethostname());
        }

        $sql = "SELECT access_key, secret_key, region, url FROM {$this->dbTable} WHERE id = '{$name}'";
        $queueConfig = $this->db->query($sql)->fetch();

        if (!$queueConfig) {
            throw new ApplicationException('No queue configuration found in DB.');
        }

        $this->db->close();
        return new QueueService($queueConfig, $this->componentName);
    }

    public function create($name, $region = 'us-east-1', $key = null, $secret = null)
    {
        $data = [
            'region' => $region,
            'version' => '2012-11-05',
            'retries' => 40,
            'http' => [
                'connect_timeout' => 10,
                'timeout' => 120,
            ],
            'debug' => [
                'http' => true,
                'scrub_auth' => true,
                'retries' => true,
                'stream_size' => 0,
                'timer' => true
            ]
        ];

        if ($key != null && $secret != null) {
            $data['credentials'] = [
                'key' => $key,
                'secret' => $secret
            ];
        }

        $sqsClient = new SqsClient($data);

        $sqsQueue = $sqsClient->createQueue([
            'QueueName' => $name
        ]);

        return $sqsQueue;
    }
}
