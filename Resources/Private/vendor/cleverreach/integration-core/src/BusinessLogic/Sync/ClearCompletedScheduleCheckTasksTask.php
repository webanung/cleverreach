<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Scheduler\ScheduleCheckTask;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Task;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 * Class ClearCompletedScheduleCheckTasksTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class ClearCompletedScheduleCheckTasksTask extends Task
{
    const INITIAL_PROGRESS_PERCENT = 10;

    const HOURS = 1;

    /**
     * Removes all completed ScheduleCheckTask items which are older than 1 hour
     */
    public function execute()
    {
        $this->reportProgress(self::INITIAL_PROGRESS_PERCENT);
        /** @var TaskQueueStorage $taskQueueStorage */
        $taskQueueStorage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        $taskQueueStorage->deleteCompletedQueueItems(ScheduleCheckTask::getClassName(), $this->getFinishedTimestamp());

        $this->reportProgress(100);
    }

    /**
     * Returns queue item finish timestamp.
     *
     * @return int
     */
    private function getFinishedTimestamp()
    {
        /** @var TimeProvider $timeProvider */
        $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        $currentTimestamp = $timeProvider->getCurrentLocalTime()->getTimestamp();

        return $currentTimestamp - self::HOURS * 3600;
    }
}
