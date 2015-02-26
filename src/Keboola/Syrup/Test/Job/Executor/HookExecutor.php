<?php
namespace Keboola\Syrup\Test\Job\Executor;

use Keboola\Syrup\Elasticsearch\Job as ElasticsearchJob;
use Keboola\Syrup\Job\HookExecutorInterface;
use Keboola\Syrup\Job\Metadata\Job;

class HookExecutor extends \Keboola\Syrup\Job\Executor implements HookExecutorInterface
{
    const HOOK_RESULT_KEY = 'postExecution';
    const HOOK_RESULT_VALUE = 'done';

    /**
     * @var Job
     */
    private $job;

    /**
     * @var ElasticsearchJob
     */
    private $elasticsearchJob;

    public function __construct(ElasticsearchJob $elasticsearchJob)
    {
        $this->elasticsearchJob = $elasticsearchJob;
    }

    /**
     * @param Job $job
     * @return array
     */
    public function execute(Job $job)
    {
        parent::execute($job);

        $this->job = $job;

        return ['testing' => 'HookExecutor'];
    }

    /**
     * Hook for modify job after job execution
     *
     * @param Job $job
     * @return void
     */
    public function postExecution(Job $job)
    {
        if ($job->getId() !== $this->job->getId()) {
            throw new \InvalidArgumentException('Given job must be same as previous executed');
        }

        if ($job->getComponent() !== $this->job->getComponent()) {
            throw new \InvalidArgumentException('Given job must be same as previous executed');
        }

        $job->setResult($job->getResult() + array(self::HOOK_RESULT_KEY => self::HOOK_RESULT_VALUE));

        $this->elasticsearchJob->update($job);
    }
}
