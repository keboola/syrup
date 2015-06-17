<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 28/05/14
 * Time: 14:37
 */

namespace Keboola\Syrup\Job\Metadata;

use Keboola\Syrup\Exception\ApplicationException;

class Job implements JobInterface
{
    const STATUS_WAITING = 'waiting';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_ERROR = 'error';
    const STATUS_WARNING = 'warning';
    const STATUS_TERMINATING = 'terminating';
    const STATUS_TERMINATED = 'terminated';

    const ERROR_USER = 'user';
    const ERROR_APPLICATION = 'application';

    protected $index;
    protected $version;
    protected $type;

    protected $data = [
        'id' => null,
        'runId' => null,
        'lockName' => null,
        'project' => [
            'id' => null,
            'name' => null
        ],
        'token' => [
            'id' => null,
            'description' => null,
            'token' => null
        ],
        'component' => null,
        'command' => null,
        'params' => [],
        'result' => [],
        'status' => null,
        'process' => [
            'host' => null,
            'pid' => null
        ],
        'createdTime' => null,
        'startTime' => null,
        'endTime' => null,
        'durationSeconds' => null,
        'waitSeconds' => null,
        'nestingLevel' => 0,
        'error' => null,
        'errorNote' => null
    ];

    public function __construct(array $data = [], $index = null, $type = null, $version = null)
    {
        $this->data['status'] = self::STATUS_WAITING;
        $this->data = array_merge($this->data, $data);

        // make sure jobId is integer
        $this->setId($this->data['id']);

        if (null == $this->data['lockName']) {
            $this->setLockName($this->getComponent() . '-' . $this->getProject()['id']);
        }

        if (null != $this->data['runId']) {
            $this->data['nestingLevel'] = $this->calculateNestingLevel($this->data['runId']);
        }

        $this->index = $index;
        $this->type = $type;
        $this->version = $version;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getVersion()
    {
        return $this->version;
    }


    public function getId()
    {
        return (int) $this->data['id'];
    }

    public function setId($id)
    {
        $this->data['id'] = (int) $id;
        return $this;
    }

    public function getProject()
    {
        return $this->data['project'];
    }

    /**
     * @param array $project
     * - id
     * - name
     * @return $this
     */
    public function setProject(array $project)
    {
        $this->checkArrayKeys($project, ['id', 'name']);
        $this->data['project'] = $project;
        return $this;
    }

    public function getToken()
    {
        return $this->data['token'];
    }

    public function setToken(array $token)
    {
        $this->checkArrayKeys($token, ['id', 'description', 'token']);
        $this->data['token'] = $token;
        return $this;
    }

    public function getCommand()
    {
        return $this->data['command'];
    }

    public function setCommand($cmd)
    {
        $this->data['command'] = $cmd;
        return $this;
    }

    public function getStatus()
    {
        return $this->data['status'];
    }

    public function setStatus($status)
    {
        $this->data['status'] = $status;
        return $this;
    }

    public function getComponent()
    {
        return $this->data['component'];
    }

    public function setComponent($component)
    {
        $this->data['component'] = $component;
        return $this;
    }

    public function setResult($result)
    {
        $this->data['result'] = $result;
        return $this;
    }

    public function getResult()
    {
        return $this->data['result'];
    }

    public function getRunId()
    {
        return $this->data['runId'];
    }

    public function setRunId($runId)
    {
        $this->data['runId'] = $runId;
        $this->data['nestingLevel'] = $this->calculateNestingLevel($runId);
    }

    public function setLockName($lockName)
    {
        $this->data['lockName'] = $lockName;
    }

    public function getLockName()
    {
        return $this->data['lockName'];
    }

    public function setParams(array $params)
    {
        $this->data['params'] = $params;
    }

    public function getParams()
    {
        return $this->data['params'];
    }

    public function getProcess()
    {
        return $this->data['process'];
    }

    public function setProcess(array $process)
    {
        $this->checkArrayKeys($process, ['host', 'pid']);
        $this->data['process'] = $process;
        return $this;
    }

    public function getCreatedTime()
    {
        return $this->data['createdTime'];
    }

    public function setCreatedTime($datetime)
    {
        $this->data['createdTime'] = $datetime;
        return $this;
    }

    public function getStartTime()
    {
        return $this->data['startTime'];
    }

    public function setStartTime($datetime)
    {
        $this->data['startTime'] = $datetime;
        return $this;
    }

    public function getEndTime()
    {
        return $this->data['endTime'];
    }

    public function setEndTime($datetime)
    {
        $this->data['endTime'] = $datetime;
        return $this;
    }

    public function getDurationSeconds()
    {
        return $this->data['durationSeconds'];
    }

    public function setDurationSeconds($seconds)
    {
        $this->data['durationSeconds'] = $seconds;
        return $this;
    }

    public function getWaitSeconds()
    {
        return $this->data['waitSeconds'];
    }

    public function setWaitSeconds($seconds)
    {
        $this->data['waitSeconds'] = $seconds;
        return $this;
    }

    public function getNestingLevel()
    {
        return $this->data['nestingLevel'];
    }

    public function setError($error)
    {
        if (!in_array($error, [self::ERROR_USER, self::ERROR_APPLICATION])) {
            throw new ApplicationException(sprintf("Error must be one of 'user' or 'application'. Provided '%s'", $error));
        }

        $this->data['error'] = $error;
        return $this;
    }

    public function getError()
    {
        return $this->data['error'];
    }

    public function setErrorNote($note)
    {
        $this->data['errorNote'] = $note;
        return $this;
    }

    public function getErrorNote()
    {
        return $this->data['errorNote'];
    }

    public function setAttribute($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getAttribute($key)
    {
        return $this->data[$key];
    }

    public function getData()
    {
        return $this->data;
    }

    public function getLogData()
    {
        $logData = $this->data;
        unset($logData['token']);
        return $logData;
    }

    public function validate()
    {
        $allowedStatuses = [
            self::STATUS_WAITING,
            self::STATUS_PROCESSING,
            self::STATUS_SUCCESS,
            self::STATUS_ERROR,
            self::STATUS_CANCELLED,
            self::STATUS_WARNING,
            self::STATUS_TERMINATING,
            self::STATUS_TERMINATED,
        ];

        if (!in_array($this->getStatus(), $allowedStatuses)) {
            throw new ApplicationException(
                "Job status has unrecongized value '"
                . $this->getStatus() . "'. Job status must be one of ("
                . implode(',', $allowedStatuses) . ")"
            );
        }
    }

    protected function checkArrayKeys($array, $keys)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new ApplicationException(sprintf("Missing key '%s'", $key));
            }
        }
    }

    protected function calculateNestingLevel($runId)
    {
        return substr_count($runId, '.');
    }
}
