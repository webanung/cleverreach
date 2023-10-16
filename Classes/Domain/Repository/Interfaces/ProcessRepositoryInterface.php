<?php

namespace CR\OfficialCleverreach\Domain\Repository\Interfaces;

use CR\OfficialCleverreach\Domain\Model\Process;

/**
 * Interface ProcessRepositoryInterface
 * @package CR\OfficialCleverreach\Domain\Repository\Interfaces
 */
interface ProcessRepositoryInterface
{
    /**
     * Finds process identified by provided guid.
     *
     * @param string $guid
     *
     * @return Process|null
     */
    public function find($guid);

    /**
     * Saves process.
     *
     * @param Process $process
     */
    public function save(Process $process);

    /**
     * Deletes a process identified by provided guid.
     *
     * @param string $guid
     */
    public function delete($guid);
}
