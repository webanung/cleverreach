<?php

namespace CleverReach\Infrastructure\Interfaces\Required;

/**
 * Interface Serializable
 *
 * @package CleverReach\Infrastructure\Interfaces\Required
 */
interface Serializable extends \Serializable
{
    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array);

    /**
     * Transforms entity to array.
     *
     * @return array
     */
   public function toArray();
}
