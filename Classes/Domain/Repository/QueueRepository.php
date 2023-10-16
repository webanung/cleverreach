<?php

namespace WebanUg\Cleverreach\Domain\Repository;

use CleverReach\Infrastructure\TaskExecution\QueueItem;
use WebanUg\Cleverreach\Domain\Model\Queue;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\QueueRepositoryInterface;
use Doctrine\DBAL\FetchMode;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class QueueRepository
 * @package WebanUg\Cleverreach\Domain\Repository
 */
class QueueRepository extends AbstractRepository implements QueueRepositoryInterface
{
    const TABLE_NAME = 'tx_officialcleverreach_domain_model_queue';

    /**
     * Finds items by given conditions.
     *
     * @param array $conditions (['column_name' => 'column_value'])
     * @param array $sort (['property_name' => direction])
     * @param int $limit
     * @param int $offset
     *
     * @return Queue[]
     */
    public function find(array $conditions, array $sort = [], $limit = null, $offset = null)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME);

        foreach ($conditions as $key => $value) {
            if ($value === null) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNull($key));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($key, $queryBuilder->createNamedParameter($value)));
            }
        }

        foreach ($sort as $field => $direction) {
            $queryBuilder->addOrderBy($field, $direction);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        $query = $queryBuilder->execute();
        $result = $query->fetch(FetchMode::ASSOCIATIVE);

        if (empty($result)) {
            return [];
        }

        return $this->transformToQueueItems($result, $query->rowCount());
    }

    /**
     * Finds one item by given conditions.
     *
     * @param array $conditions (['column_name' => 'column_value'])
     * @param array $sort
     *
     * @return null|Queue
     */
    public function findOne(array $conditions, array $sort = [])
    {
        $results = $this->find($conditions, $sort, 1);
        if (!empty($results)) {
            return $results[0];
        }

        return null;
    }

    /**
     * Finds latest queue item by type.
     *
     * @param string $type
     *
     * @return null|Queue
     */
    public function findLatest($type)
    {
        return $this->findOne(['type' => $type], ['queueTimestamp' => QueryInterface::ORDER_DESCENDING]);
    }

    /**
     * Saves queue item.
     *
     * @param Queue $queue
     *
     * @return int
     */
    public function save(Queue $queue)
    {
        if (empty($queue->getUid())) {
            return $this->insert($queue);
        }

        return $this->update($queue);
    }

    /**
     * Finds oldest queued items.
     *
     * @param int $limit
     *
     * @return Queue[]
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findOldestQueuedItems($limit = 10)
    {
        $tableName = static::TABLE_NAME;
        $queryBuilder = $this->getQueryBuilder();

        $runningQueuesQuery = "SELECT queueName FROM $tableName q2 WHERE q2.status = '"
            . QueueItem::IN_PROGRESS . "'";

        $sql = "SELECT * 
                FROM (
                  SELECT queueName, min(uid) as uid
                  FROM $tableName as t
                  WHERE t.status='" . QueueItem::QUEUED . "' AND t.queueName NOT IN ($runningQueuesQuery)
                  GROUP BY queueName LIMIT $limit
                ) as queueView 
                INNER JOIN $tableName as queueTable
                ON queueView.queueName = queueTable.queueName and queueView.uid = queueTable.uid";

        $query = $queryBuilder->getConnection()->query($sql);
        $result = $query->fetch(FetchMode::ASSOCIATIVE);

        if (empty($result)) {
            return [];
        }

        return $this->transformToQueueItems($result, $query->rowCount());
    }

    /**
     * Updates an existing queue item and returns its ID.
     *
     * @param Queue $queue
     *
     * @return int
     */
    private function update(Queue $queue)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->update(static::TABLE_NAME);

        foreach ($queue->toArray() as $key => $value) {
            $queryBuilder->set($key, $value);
        }

        $queryBuilder->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($queue->getUid())))
            ->execute();

        return $queue->getUid();
    }

    /**
     * Transform the resulting rows from select query to queue item models.
     * If there is only one row in the select query result, the result won't
     * be an array of arrays, but instead it will just represent that one row
     * as an associative array of its columns and their values.
     *
     * @param array $result
     * @param int $numberOfRows
     *
     * @return Queue[]
     */
    private function transformToQueueItems(array $result, $numberOfRows)
    {
        if ($numberOfRows === 1) {
            $rows = [$result];
        } else {
            $rows = $result;
        }

        return Queue::fromBatch($rows);
    }
}
