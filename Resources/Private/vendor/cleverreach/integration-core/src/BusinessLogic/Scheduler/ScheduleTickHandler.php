<?php

namespace CleverReach\BusinessLogic\Scheduler;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;

/**
 * Class ScheduleTickHandler
 *
 * @package CleverReach\BusinessLogic\Scheduler
 */
class ScheduleTickHandler
{
    /**
     * Queues ScheduleCheckTask.
     */
    public function handle()
    {
        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $task = $queueService->findLatestByType('ScheduleCheckTask');
        $threshold = $configService->getSchedulerTimeThreshold();

        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS))) {
            return;
        }

        if ($task === null || $task->getQueueTimestamp() + $threshold < time()) {
            $task = new ScheduleCheckTask();
            try {
                $queueService->enqueue($configService->getSchedulerQueueName(), $task);
            } catch (QueueStorageUnavailableException $ex) {
                Logger::logDebug(
                    json_encode(array(
                        'Message' => 'Failed to enqueue task ' . $task->getType(),
                        'ExceptionMessage' => $ex->getMessage(),
                        'ExceptionTrace' => $ex->getTraceAsString()
                    ))
                );
            }
        }
    }
}
