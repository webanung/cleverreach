<?php

namespace CleverReach\Infrastructure\Interfaces\Exposed;

use CleverReach\Infrastructure\Interfaces\Required\Serializable;

/**
 * Interface Runnable
 *
 * @package CleverReach\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends Serializable
{
    /**
     * Starts runnable run logic.
     */
    public function run();
}
