<?php

namespace CR\OfficialCleverreach\IntegrationServices\Sync;

use CleverReach\BusinessLogic\Sync\InitialSyncTask as CoreInitialSyncTask;

class InitialSyncTask extends CoreInitialSyncTask
{
    /**
     * Sets tasks for first group of tasks in initial sync (UpdateNewsletterStatus, Attributes and Filter) to the list
     * of sub tasks.
     *
     * @param array $subTasks
     */
    protected function setFieldsTasks(array &$subTasks)
    {
        // Class name and percentage of progress this task takes from the overall progress
        $subTasks[$this->getUpdateNewsletterStatusSyncTaskName()] = 10;
        $subTasks[$this->getAttributesSyncTaskName()] = 10;
        $subTasks[$this->getFilterSyncTaskName()] = 10;
    }

    /**
     * Gets overall progress of tasks belonging to fields task group.
     *
     * @return float
     */
    protected function getFieldsTasksProgress()
    {
        return $this->taskProgressMap[$this->getAttributesSyncTaskName()] / 3 +
            $this->taskProgressMap[$this->getFilterSyncTaskName()] / 3 +
            $this->taskProgressMap[$this->getUpdateNewsletterStatusSyncTaskName()] / 3;
    }

    /**
     * @param string $taskKey
     *
     * @return \CleverReach\Infrastructure\TaskExecution\Task
     */
    protected function createSubTask($taskKey)
    {
        if ($taskKey === $this->getUpdateNewsletterStatusSyncTaskName()) {
            return new UpdateNewsletterStatusSyncTask();
        }

        return parent::createSubTask($taskKey);
    }

    /**
     * @return string
     */
    private function getUpdateNewsletterStatusSyncTaskName()
    {
        return UpdateNewsletterStatusSyncTask::getClassName();
    }
}
