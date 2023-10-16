<?php

namespace CR\OfficialCleverreach\Domain\Repository\Legacy;

use CR\OfficialCleverreach\Domain\Model\Process;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\ProcessRepositoryInterface;

/**
 * Class ProcessRepository
 * @package CR\OfficialCleverreach\Domain\Repository\Legacy
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
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'guid, runner',
            static::TABLE_NAME,
            'guid="' . $guid . '"'
        );

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
        $this->getDatabaseConnection()->exec_DELETEquery(static::TABLE_NAME, 'guid="' . $guid . '"');
    }

    /**
     * Updates an existing process.
     *
     * @param Process $process
     */
    private function update(Process $process)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            static::TABLE_NAME,
            'guid="' . $process->getGuid() . '"',
            ['runner' => $process->getRunner()]
        );
    }
}
