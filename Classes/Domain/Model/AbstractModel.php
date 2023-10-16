<?php

namespace WebanUg\Cleverreach\Domain\Model;

/**
 * Class AbstractModel
 * @package CR\OfficialCleverreach\Domain\Model
 */
abstract class AbstractModel
{
    /**
     * Creates list of DTOs from a batch of raw data.
     *
     * @param array $batch Batch of raw data.
     *
     * @return array List of DTO instances.
     */
    public static function fromBatch(array $batch)
    {
        $result = array();

        foreach ($batch as $index => $item) {
            $result[$index] = static::fromArray($item);
        }

        return $result;
    }

    /**
     * Transforms raw array data to its model.
     *
     * @param array $raw
     *
     * @return static
     */
    public static function fromArray(array $raw)
    {
        $instance = new static();

        foreach ($raw as $field => $value) {
            if (property_exists($instance, $field)) {
                $instance->$field = $value;
            }
        }

        return $instance;
    }

    /**
     * Transforms model DTO object to array.
     *
     * @return array Array representation of a DTO object.
     */
    public function toArray()
    {
        return array();
    }
}
