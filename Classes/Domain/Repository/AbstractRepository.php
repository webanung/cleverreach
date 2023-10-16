<?php

namespace CR\OfficialCleverreach\Domain\Repository;

use CR\OfficialCleverreach\Domain\Model\AbstractModel;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Class AbstractRepository
 * @package CR\OfficialCleverreach\Domain\Repository
 */
abstract class AbstractRepository
{
    const TABLE_NAME = '';

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * ConfigurationRepository constructor.
     *
     * @param ConnectionPool $connectionPool
     */
    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Inserts model into its corresponding database table.
     *
     * @param \CR\OfficialCleverreach\Domain\Model\AbstractModel $model
     *
     * @return int
     */
    protected function insert(AbstractModel $model)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->insert(static::TABLE_NAME)
            ->values($model->toArray())
            ->execute();

        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    /**
     * Returns an instance of query builder.
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->connectionPool->getQueryBuilderForTable(static::TABLE_NAME);
    }
}
