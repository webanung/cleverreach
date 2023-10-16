<?php

namespace CR\OfficialCleverreach\Domain\Repository\Interfaces;

use CR\OfficialCleverreach\Domain\Model\Queue;

/**
 * Interface QueueRepositoryInterface
 * @package CR\OfficialCleverreach\Domain\Repository\Interfaces
 */
interface QueueRepositoryInterface
{
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
    public function find(array $conditions, array $sort = [], $limit = null, $offset = null);

    /**
     * Finds one item by given conditions.
     *
     * @param array $conditions (['column_name' => 'column_value'])
     * @param array $sort
     *
     * @return null|Queue
     */
    public function findOne(array $conditions, array $sort = []);

    /**
     * Finds latest queue item by type.
     *
     * @param string $type
     *
     * @return null|Queue
     */
    public function findLatest($type);

    /**
     * Saves queue item.
     *
     * @param Queue $queue
     *
     * @return int
     */
    public function save(Queue $queue);

    /**
     * Finds oldest queued items.
     *
     * @param int $limit
     *
     * @return Queue[]
     */
    public function findOldestQueuedItems($limit = 10);
}
