<?php

namespace CR\OfficialCleverreach\Domain\Model;

/**
 * Class Queue
 * @package CR\OfficialCleverreach\Domain\Model
 */
class Queue extends AbstractModel
{
    /**
     * @var int
     */
    protected $uid;
    /**
     * @var string
     */
    protected $status;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $queueName;
    /**
     * @var string
     */
    protected $progress;
    /**
     * @var string
     */
    protected $retries;
    /**
     * @var string
     */
    protected $failureDescription;
    /**
     * @var string
     */
    protected $serializedTask;
    /**
     * @var string
     */
    protected $createTimestamp;
    /**
     * @var string
     */
    protected $queueTimestamp;
    /**
     * @var string
     */
    protected $lastUpdateTimestamp;
    /**
     * @var string
     */
    protected $startTimestamp;
    /**
     * @var string
     */
    protected $finishTimestamp;
    /**
     * @var string
     */
    protected $lastExecutionProgress;
    /**
     * @var string
     */
    protected $failTimestamp;

    /**
     * Transforms model to its array format.
     *
     * @return array Model in array format.
     */
    public function toArray()
    {
        return [
            'status' => $this->status,
            'type' => $this->type,
            'queueName' => $this->queueName,
            'progress' => $this->progress,
            'retries' => $this->retries,
            'failureDescription' => $this->failureDescription,
            'serializedTask' => $this->serializedTask,
            'createTimestamp' => $this->createTimestamp,
            'queueTimestamp' => $this->queueTimestamp,
            'lastUpdateTimestamp' => $this->lastUpdateTimestamp,
            'startTimestamp' => $this->startTimestamp,
            'finishTimestamp' => $this->finishTimestamp,
            'failTimestamp' => $this->failTimestamp,
            'lastExecutionProgress' => $this->lastExecutionProgress,
        ];
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        $this->queueName = $queueName;
    }

    /**
     * @return string
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param string $progress
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    /**
     * @return string
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param string $retries
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    /**
     * @return string
     */
    public function getFailureDescription()
    {
        return $this->failureDescription;
    }

    /**
     * @param string $failureDescription
     */
    public function setFailureDescription($failureDescription)
    {
        $this->failureDescription = $failureDescription;
    }

    /**
     * @return string
     */
    public function getSerializedTask()
    {
        return $this->serializedTask;
    }

    /**
     * @param string $serializedTask
     */
    public function setSerializedTask($serializedTask)
    {
        $this->serializedTask = $serializedTask;
    }

    /**
     * @return string
     */
    public function getCreateTimestamp()
    {
        return $this->createTimestamp;
    }

    /**
     * @param string $createTimestamp
     */
    public function setCreateTimestamp($createTimestamp)
    {
        $this->createTimestamp = $createTimestamp;
    }

    /**
     * @return string
     */
    public function getQueueTimestamp()
    {
        return $this->queueTimestamp;
    }

    /**
     * @param string $queueTimestamp
     */
    public function setQueueTimestamp($queueTimestamp)
    {
        $this->queueTimestamp = $queueTimestamp;
    }

    /**
     * @return string
     */
    public function getLastUpdateTimestamp()
    {
        return $this->lastUpdateTimestamp;
    }

    /**
     * @param string $lastUpdateTimestamp
     */
    public function setLastUpdateTimestamp($lastUpdateTimestamp)
    {
        $this->lastUpdateTimestamp = $lastUpdateTimestamp;
    }

    /**
     * @return string
     */
    public function getStartTimestamp()
    {
        return $this->startTimestamp;
    }

    /**
     * @param string $startTimestamp
     */
    public function setStartTimestamp($startTimestamp)
    {
        $this->startTimestamp = $startTimestamp;
    }

    /**
     * @return string
     */
    public function getFinishTimestamp()
    {
        return $this->finishTimestamp;
    }

    /**
     * @param string $finishTimestamp
     */
    public function setFinishTimestamp($finishTimestamp)
    {
        $this->finishTimestamp = $finishTimestamp;
    }

    /**
     * @return string
     */
    public function getLastExecutionProgress()
    {
        return $this->lastExecutionProgress;
    }

    /**
     * @param string $lastExecutionProgress
     */
    public function setLastExecutionProgress($lastExecutionProgress)
    {
        $this->lastExecutionProgress = $lastExecutionProgress;
    }

    /**
     * @return string
     */
    public function getFailTimestamp()
    {
        return $this->failTimestamp;
    }

    /**
     * @param string $failTimestamp
     */
    public function setFailTimestamp($failTimestamp)
    {
        $this->failTimestamp = $failTimestamp;
    }
}