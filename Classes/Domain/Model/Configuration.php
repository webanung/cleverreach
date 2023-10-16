<?php

namespace WebanUg\Cleverreach\Domain\Model;

/**
 * Class Configuration
 * @package CR\OfficialCleverreach\Domain\Model
 */
class Configuration extends AbstractModel
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $value;

    /**
     * Transforms raw array data to its model.
     *
     * @param array $raw
     *
     * @return static
     */
    public static function fromArray(array $raw)
    {
        return new static($raw['cr_key'], $raw['cr_value']);
    }

    /**
     * Configuration constructor.
     *
     * @param string $key
     * @param string $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Transforms model DTO object to array.
     *
     * @return array Array representation of a DTO object.
     */
    public function toArray()
    {
        return array(
            'cr_key' => $this->key,
            'cr_value' => $this->value,
        );
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
