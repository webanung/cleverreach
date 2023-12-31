<?php

namespace CleverReach\Infrastructure\Utility;

/**
 * Class NativeSerializer
 *
 * @package CleverReach\Infrastructure\Utility
 */
class NativeSerializer extends Serializer
{
    /**
     * Performs concrete serialization.
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function doSerialize($data)
    {
        return serialize($data);
    }

    /**
     * Performs concrete unserialization.
     *
     * @param string $data
     *
     * @return mixed
     */
    protected function doUnserialize($data)
    {
        return unserialize($data);
    }
}
