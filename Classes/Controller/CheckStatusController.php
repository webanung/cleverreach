<?php

namespace CR\OfficialCleverreach\Controller;

require_once __DIR__ . '/../autoload.php';

use CleverReach\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CheckStatusController extends ActionController
{
    /**
     * @var Queue $queue
     */
    private $queue;

    /**
     * @return string
     */
    public function userInfoAction()
    {
        $status = 'finished';
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @var QueueItem $queueItem */
        $queueItem = $queue->findLatestByType(RefreshUserInfoTask::getClassName());

        if ($queueItem !== null) {
            $queueStatus = $queueItem->getStatus();
            if ($queueStatus !== QueueItem::FAILED && $queueStatus !== QueueItem::COMPLETED) {
                $status = 'in_progress';
            }
        }

        return json_encode(['status' => $status]);
    }

    /**
     * @return string
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function initialSyncAction()
    {
        /** @var QueueItem $queueItem */
        $queueItem = $this->getQueueService()->findLatestByType(InitialSyncTask::getClassName());
        if ($queueItem === null) {
            return json_encode(['status' => QueueItem::FAILED,]);
        }

        /** @var InitialSyncTask $initialSyncTask */
        $initialSyncTask = $queueItem->getTask();
        $initialSyncTaskProgress = $initialSyncTask->getProgressByTask();

        return json_encode(
            [
                'status' => $queueItem->getStatus(),
                'taskStatuses' => [
                    'subscriberlist' => [
                        'status' => $this->getStatus($initialSyncTaskProgress['subscriberList']),
                        'progress' => $initialSyncTaskProgress['subscriberList'],
                    ],
                    'add_fields' => [
                        'status' => $this->getStatus($initialSyncTaskProgress['fields']),
                        'progress' => $initialSyncTaskProgress['fields'],
                    ],
                    'recipient_sync' => [
                        'status' => $this->getStatus($initialSyncTaskProgress['recipients']),
                        'progress' => $initialSyncTaskProgress['recipients'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param float $progress
     *
     * @return string
     */
    private function getStatus($progress)
    {
        $status = QueueItem::QUEUED;
        if (0 < $progress && $progress < 100) {
            $status = QueueItem::IN_PROGRESS;
        } elseif ($progress >= 100) {
            $status = QueueItem::COMPLETED;
        }

        return $status;
    }

    /**
     * @return Queue
     */
    private function getQueueService()
    {
        if ($this->queue === null) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }
}
