<?php
namespace Keboola\Syrup\Exception;

use Keboola\Syrup\Job\Metadata\Job;

class JobException extends SyrupComponentException
{
    /**
     * @var array
     */
    protected $result = array();

    protected $status = Job::STATUS_ERROR;

    /**
     * Get job result
     *
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set job result
     *
     * @param array $result
     * @return $this
     */
    public function setResult(array $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * Get job status
     *
     * Default value is Job::STATUS_ERROR
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set job status
     *
     * @param string $status
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setStatus($status)
    {
        $allowedStatuses = array(
            Job::STATUS_ERROR,
            Job::STATUS_SUCCESS,
            Job::STATUS_WARNING,
            Job::STATUS_TERMINATED
        );

        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException(sprintf('Status "%s" is not allowed in JobException', $status));
        }

        $this->status = (string) $status;
        return $this;
    }
}
