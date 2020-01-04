<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 29/05/14
 * Time: 15:49
 */

namespace Keboola\Syrup\Service\Queue;

use Aws\Sqs\SqsClient;
use Keboola\Syrup\Exception\ApplicationException;

class QueueService
{
    /**
     * @var SqsClient
     */
    protected $client;
    protected $queueUrl;
    protected $componentName;

    public function __construct(array $config, $componentName)
    {
        $data = [
            'region' => $config['region'],
            'version' => '2012-11-05',
            'retries' => 40,
            'debug' => [
                'http' => true,
                'scrub_auth' => true,
                'retries' => true,
                'stream_size' => 0
            ]
        ];

        if (isset($config['access_key'])
            && isset($config['secret_key'])
            && !empty($config['access_key'])
            && !empty($config['secret_key'])) {
            $data['credentials'] = [
                'key' => $config['access_key'],
                'secret' => $config['secret_key']
            ];
        }

        $this->client = new SqsClient($data);
        $this->queueUrl = $config['url'];
        $this->componentName = $componentName;
    }

    /**
     * @param $jobId int
     * @param $data array
     * @param $delay int
     * @return int $messageId
     */
    public function enqueue($jobId, $data = [], $delay = 0)
    {
        $job = [
            'jobId' => $jobId,
            'component' => $this->componentName
        ];

        if (count($data)) {
            $job = array_merge($job, $data);
        }

        $message = $this->client->sendMessage([
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => json_encode($job),
            'DelaySeconds' => $delay,
        ]);
        return $message['MessageId'];
    }

    /**
     * @param int $messagesCount
     * @return array of QueueMessage
     */
    public function receive($messagesCount = 1)
    {
        $result = $this->client->receiveMessage([
            'QueueUrl' => $this->queueUrl,
            'WaitTimeSeconds' => 20,
            'VisibilityTimeout' => 3600,
            'MaxNumberOfMessages' => $messagesCount,
        ]);

        $queueUrl = $this->queueUrl;

        return array_map(function ($message) use ($queueUrl) {

            $body = json_decode($message['Body']);

            if (!is_object($body)) {
                $this->client->deleteMessage([
                    'QueueUrl' => $queueUrl,
                    'ReceiptHandle' => $message['ReceiptHandle'],
                ]);

                throw new ApplicationException("Corrupted message {$message['MessageId']} received. Message was deleted from SQS.", null, [
                    'message' => $message
                ]);
            }

            return new QueueMessage(
                $message['MessageId'],
                $body,
                $message['ReceiptHandle'],
                $queueUrl
            );
        }, (array) $result['Messages']);
    }

    public function deleteMessage(QueueMessage $message)
    {
        $this->client->deleteMessage([
            'QueueUrl' => $message->getQueueUrl(),
            'ReceiptHandle' => $message->getReceiptHandle(),
        ]);
    }

    public function getUrl()
    {
        return $this->queueUrl;
    }

    public function getClient()
    {
        return $this->client;
    }
}
