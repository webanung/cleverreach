<?php

namespace CR\OfficialCleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CR\OfficialCleverreach\Domain\Model\Queue;
use CR\OfficialCleverreach\Domain\Repository\QueueRepository;
use CR\OfficialCleverreach\Domain\Repository\Legacy\QueueRepository as QueueLegacyRepository;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class TaskQueueStorageService implements TaskQueueStorage
{
    /**
     * @var QueueRepository $queueRepository
     */
    private $queueRepository;
    /**
     * @var ObjectManager $objectManager
     */
    private $objectManager;

    /**
     * TaskQueueStorageService constructor.
     *
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->queueRepository = $this->getQueueRepository();
    }

    /**
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @return int
     *
     * @throws QueueItemSaveException
     */
    public function save(QueueItem $queueItem, array $additionalWhere = [])
    {
        $itemId = null;
        try {
            $queueItemId = $queueItem->getId();
            if ($queueItemId === null || $queueItemId <= 0) {
                $itemId = $this->queueRepository->save($this->createModelFromQueueItem($queueItem));
            } else {
                $this->updateQueueItem($queueItem, $additionalWhere);
                $itemId = $queueItemId;
            }
        } catch (\Exception $exception) {
            throw new QueueItemSaveException('Failed to save queue item with id: ' . $itemId, $exception->getCode());
        }

        return $itemId;
    }

    /**
     * @param int $id
     *
     * @return \CleverReach\Infrastructure\TaskExecution\QueueItem|null
     */
    public function find($id)
    {
        /** @var Queue $queueModel */
        $queueModel = $this->queueRepository->findOne(['uid' => $id]);

        return $this->convertModelToQueueItem($queueModel);
    }

    /**
     * @param string $type
     * @param string $context
     *
     * @return \CleverReach\Infrastructure\TaskExecution\QueueItem|null
     */
    public function findLatestByType($type, $context = '')
    {
        /** @var Queue $queueModel */
        $queueModel = $this->queueRepository->findLatest($type);

        return $this->convertModelToQueueItem($queueModel);
    }

    /**
     * @param int $limit
     *
     * @return QueueItem[]
     */
    public function findOldestQueuedItems($limit = 10)
    {
        return $this->formatQueueItems($this->queueRepository->findOldestQueuedItems($limit));
    }

    /**
     * @param array $filterBy
     * @param array $sortBy
     * @param int $start
     * @param int $limit
     *
     * @return QueueItem[]
     */
    public function findAll(array $filterBy = [], array $sortBy = [], $start = 0, $limit = 10)
    {
        $allQueueModels = $this->queueRepository->find($filterBy, $sortBy, $limit, $start);

        return $this->formatQueueItems($allQueueModels);
    }

    /**
     * @param $type
     * @param $timestamp
     *
     * @return mixed
     * @throws \Exception
     */
    public function deleteCompletedQueueItems($type, $timestamp = null)
    {
        // Not implemented since this method is call in ClearCompletedScheduleCheckTasksTask,
        // and scheduler is not used in TYPO 3
        throw new \Exception('deleteCompletedQueueItems not implemented!');
    }

    /**
     * @param array $excludeTypes
     * @param $timestamp
     * @param $limit
     *
     * @return int
     * @throws \Exception
     */
    public function deleteBy(array $excludeTypes = array(), $timestamp = null, $limit = 1000)
    {
        // Not implemented since this method is call in ClearCompletedTasksTask,
        // and this task is not used in TYPO 3 integration
        throw new \Exception('deleteCompletedQueueItems not implemented!');
    }

    /**
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     * @return int
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    private function updateQueueItem(QueueItem $queueItem, $additionalWhere)
    {
        $conditions = array_merge(['uid' => $queueItem->getId()], $additionalWhere);
        /** @var Queue $modelQueueItem */
        $modelQueueItem = $this->queueRepository->findOne($conditions);
        $this->checkIfRecordWithWhereConditionsExists($modelQueueItem, $conditions);
        $this->modifyQueueModel($queueItem, $modelQueueItem);

        return $this->queueRepository->save($modelQueueItem);
    }

    /**
     * @param object $model
     * @param array $additionalWhere
     *
     * @throws QueueItemSaveException
     */
    private function checkIfRecordWithWhereConditionsExists($model, array $additionalWhere)
    {
        if ($model === null) {
            Logger::logDebug(\json_encode(array(
                'Message' => 'Failed to save queue item, update condition not met.',
                'WhereCondition' => $additionalWhere,
            )));

            throw new QueueItemSaveException('Failed to save queue item, update condition not met.');
        }
    }

    /**
     * @param Queue $queueModel
     *
     * @return QueueItem|null
     */
    private function convertModelToQueueItem($queueModel)
    {
        if ($queueModel === null) {
            return null;
        }

        $queueItem = new QueueItem();
        $queueItem->setId((int)$queueModel->getUid());
        $queueItem->setStatus($queueModel->getStatus());
        $queueItem->setQueueName($queueModel->getQueueName());
        $queueItem->setProgressBasePoints((int)$queueModel->getProgress());
        $queueItem->setLastExecutionProgressBasePoints((int)$queueModel->getLastExecutionProgress());
        $queueItem->setRetries((int)$queueModel->getRetries());
        $queueItem->setFailureDescription($queueModel->getFailureDescription());
        $queueItem->setSerializedTask($queueModel->getSerializedTask());
        $queueItem->setCreateTimestamp((int)$queueModel->getCreateTimestamp());
        $queueItem->setQueueTimestamp((int)$queueModel->getQueueTimestamp());
        $queueItem->setLastUpdateTimestamp((int)$queueModel->getLastUpdateTimestamp());
        $queueItem->setStartTimestamp((int)$queueModel->getStartTimestamp());
        $queueItem->setFinishTimestamp((int)$queueModel->getFinishTimestamp());
        $queueItem->setFailTimestamp((int)$queueModel->getFailTimestamp());

        return $queueItem;
    }

    /**
     * @param QueueItem $queueItem
     *
     * @return Queue
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function createModelFromQueueItem(QueueItem $queueItem)
    {
        /** @var Queue $queueModel */
        $queueModel = $this->objectManager->get(Queue::class);
        $queueModel->setUid($queueItem->getId());
        $this->modifyQueueModel($queueItem, $queueModel);

        return $queueModel;
    }

    /**
     * @param QueueItem $queueItem
     * @param Queue $queueModel
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    private function modifyQueueModel(QueueItem $queueItem, Queue $queueModel)
    {
        $queueModel->setType($queueItem->getTaskType());
        $queueModel->setQueueName($queueItem->getQueueName());
        $queueModel->setStatus($queueItem->getStatus());
        $queueModel->setProgress($queueItem->getProgressBasePoints());
        $queueModel->setSerializedTask($queueItem->getSerializedTask());
        $queueModel->setRetries($queueItem->getRetries());
        $queueModel->setFailureDescription($queueItem->getFailureDescription());
        $queueModel->setLastExecutionProgress($queueItem->getLastExecutionProgressBasePoints());
        $queueModel->setCreateTimestamp($queueItem->getCreateTimestamp());
        $queueModel->setQueueTimestamp($queueItem->getQueueTimestamp());
        $queueModel->setStartTimestamp($queueItem->getStartTimestamp());
        $queueModel->setLastUpdateTimestamp($queueItem->getLastUpdateTimestamp());
        $queueModel->setFinishTimestamp($queueItem->getFinishTimestamp());
        $queueModel->setFailTimestamp($queueItem->getFailTimestamp());
    }

    /**
     * @param Queue[] $allQueueModels
     *
     * @return array
     */
    private function formatQueueItems($allQueueModels)
    {
        $formattedQueueItems = [];
        /** @var Queue $queueModel */
        foreach ($allQueueModels as $queueModel) {
            $formattedQueueItems[] = $this->convertModelToQueueItem($queueModel);
        }

        return $formattedQueueItems;
    }

    /**
     * Returns an instance of queue repository.
     *
     * @return \CR\OfficialCleverreach\Domain\Repository\Interfaces\QueueRepositoryInterface
     *
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    private function getQueueRepository()
    {
        if (Helper::isCurrentVersion9OrHigher()) {
            return $this->objectManager->get(QueueRepository::class);
        }

        return $this->objectManager->get(QueueLegacyRepository::class);
    }
}
