<?php

namespace CR\OfficialCleverreach\Domain\Repository\Interfaces;

use CR\OfficialCleverreach\Domain\Model\Configuration;

/**
 * Interface ConfigRepositoryInterface
 * @package CR\OfficialCleverreach\Domain\Repository\Interfaces
 */
interface ConfigRepositoryInterface
{
    /**
     * Saves configuration value.
     *
     * @param Configuration $configItem
     */
    public function save(Configuration $configItem);

    /**
     * Returns configuration value.
     *
     * @param string $key
     *
     * @return Configuration
     */
    public function get($key);
}
