<?php

namespace WebanUg\Cleverreach\Domain\Repository\Legacy;

use CleverReach\Infrastructure\TaskExecution\QueueItem;
use WebanUg\Cleverreach\Domain\Model\Queue;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\QueueRepositoryInterface;
use http\Exception\InvalidArgumentException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class QueueRepository
 * @package WebanUg\Cleverreach\Domain\Repository\Legacy
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
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            static::TABLE_NAME,
            $this->getWhereClause($conditions),
            '',
            $this->getOrderByClause($sort),
            $this->getLimitClause($limit, $offset)
        );

        if (empty($rows)) {
            return [];
        }

        return Queue::fromBatch($rows);
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
     * Finds oldest queued items.
     *
     * @param int $limit
     *
     * @return Queue[]
     */
    public function findOldestQueuedItems($limit = 10)
    {
        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows('*', $this->getOldestQueuedItemsQuery($limit), '');

        if (empty($rows)) {
            return [];
        }

        return Queue::fromBatch($rows);
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

        $this->update($queue);

        return $queue->getUid();
    }

    /**
     * Updates an existing queue item and returns its ID.
     *
     * @param Queue $queue
     */
    private function update(Queue $queue)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            static::TABLE_NAME,
            'uid=' . $queue->getUid(),
            $queue->toArray()
        );
    }

    /**
     * Returns where part of the select query.
     *
     * @param array $conditions
     *
     * @return string
     */
    private function getWhereClause(array $conditions)
    {
        if (empty($conditions)) {
            return '';
        }

        $where = [];

        foreach ($conditions as $key => $value) {
            if ($value === null) {
                $where[] = $key . ' IS NULL';
            } else {
                $where[] = $key . '=' . $this->getDatabaseConnection()->fullQuoteStr($value, static::TABLE_NAME);
            }
        }

        return implode(' AND ', $where);
    }

    /**
     * Returns order by part of the select query.
     *
     * @param array $sort
     *
     * @return string
     */
    private function getOrderByClause(array $sort)
    {
        if (empty($sort)) {
            return '';
        }

        $orderBy = [];

        foreach ($sort as $field => $direction) {
            $orderBy[] = $field . ' ' . $direction;
        }

        return implode(', ', $orderBy);
    }

    /**
     * Returns limit part of the select query.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return string
     */
    private function getLimitClause($limit, $offset) {
        $limitClause = '';

        if (!empty($offset)) {
            if (empty($limit)) {
                throw new InvalidArgumentException('Limit is mandatory if offset is provided');
            }

            $limitClause .= $offset . ',';
        }

        if (!empty($limit)) {
            $limitClause .= $limit;
        }

        return $limitClause;
    }

    /**
     * Builds and returns the query for finding the oldest queued items.
     *
     * @param int $limit
     *
     * @return string
     */
    private function getOldestQueuedItemsQuery($limit = 10)
    {
        $runningQueuesQuery = $this->getDatabaseConnection()->SELECTsubquery(
            'queueName',
            static::TABLE_NAME,
            'status = "' . QueueItem::IN_PROGRESS . '"'
        );

        $subQuery = $this->getDatabaseConnection()->SELECTquery(
            'queueName, min(uid) AS uid',
            static::TABLE_NAME . ' AS t',
            't.status="' . QueueItem::QUEUED . '" AND t.queueName NOT IN (' . $runningQueuesQuery . ')',
            'queueName',
            '',
            $limit
        );

        return '(' . $subQuery . ') AS queueView INNER JOIN ' . static::TABLE_NAME . ' AS queueTable
                ON queueView.queueName = queueTable.queueName and queueView.uid = queueTable.uid';
    }
}
