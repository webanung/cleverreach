<?php

namespace CR\OfficialCleverreach\Domain\Repository;

use CR\OfficialCleverreach\Domain\Model\Process;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\ProcessRepositoryInterface;
use Doctrine\DBAL\FetchMode;

/**
 * Class ProcessRepository
 * @package CR\OfficialCleverreach\Domain\Repository
 */
class ProcessRepository extends AbstractRepository implements ProcessRepositoryInterface
{
    const TABLE_NAME = 'tx_officialcleverreach_domain_model_process';

    /**
     * Finds process identified by provided guid.
     *
     * @param string $guid
     *
     * @return Process|null
     */
    public function find($guid)
    {
        $queryBuilder = $this->getQueryBuilder();

        $row = $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('guid', $queryBuilder->createNamedParameter($guid)))
            ->execute()
            ->fetch(FetchMode::ASSOCIATIVE);

        if (empty($row)) {
            return null;
        }

        return Process::fromArray($row);
    }

    /**
     * Saves process.
     *
     * @param Process $process
     */
    public function save(Process $process)
    {
        if ($this->find($process->getGuid()) === null) {
            $this->insert($process);
        } else {
            $this->update($process);
        }
    }

    /**
     * Deletes a process identified by provided guid.
     *
     * @param string $guid
     */
    public function delete($guid)
    {
        $process = $this->find($guid);

        if ($process !== null) {
            $queryBuilder = $this->getQueryBuilder();

            $queryBuilder->delete(static::TABLE_NAME)
                ->where($queryBuilder->expr()->eq('guid', $queryBuilder->createNamedParameter($guid)))
                ->execute();
        }
    }

    /**
     * Updates an existing process.
     *
     * @param Process $process
     */
    private function update(Process $process)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->update(static::TABLE_NAME)
            ->set('runner', $process->getRunner())
            ->where($queryBuilder->expr()->eq('guid', $queryBuilder->createNamedParameter($process->getGuid())))
            ->execute();
    }
}
