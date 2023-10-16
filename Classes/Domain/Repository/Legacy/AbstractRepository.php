<?php

namespace CR\OfficialCleverreach\Domain\Repository\Legacy;

use CR\OfficialCleverreach\Domain\Model\AbstractModel;

/**
 * Class AbstractRepository
 * @package CR\OfficialCleverreach\Domain\Repository\Legacy
 */
abstract class AbstractRepository
{
    const TABLE_NAME = '';

    /**
     * Inserts model into its corresponding database table.
     *
     * @param \CR\OfficialCleverreach\Domain\Model\AbstractModel $model
     *
     * @return int
     */
    public function insert(AbstractModel $model)
    {
        $this->getDatabaseConnection()->exec_INSERTquery(static::TABLE_NAME, $model->toArray());

        return $this->getDatabaseConnection()->sql_insert_id();
    }

    /**
     * Returns database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
