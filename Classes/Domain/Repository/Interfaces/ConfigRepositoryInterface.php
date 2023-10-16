<?php

namespace WebanUg\Cleverreach\Domain\Repository\Interfaces;

use WebanUg\Cleverreach\Domain\Model\Configuration;

/**
 * Interface ConfigRepositoryInterface
 * @package WebanUg\Cleverreach\Domain\Repository\Interfaces
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
