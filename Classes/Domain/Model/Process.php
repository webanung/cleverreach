<?php

namespace WebanUg\Cleverreach\Domain\Model;

/**
 * Class Process
 * @package CR\OfficialCleverreach\Domain\Model
 */
class Process extends AbstractModel
{
    /**
     * @var string
     */
    protected $guid = 0;
    /**
     * @var string
     */
    protected $runner = '';

    /**
     * Transforms raw array data to its model.
     *
     * @param array $raw
     *
     * @return static
     */
    public static function fromArray(array $raw)
    {
        return new static($raw['guid'], $raw['runner']);
    }

    /**
     * Process constructor.
     *
     * @param string $guid
     * @param string $runner
     */
    public function __construct($guid, $runner)
    {
        $this->guid = $guid;
        $this->runner = $runner;
    }

    /**
     * Transforms model DTO object to array.
     *
     * @return array Array representation of a DTO object.
     */
    public function toArray()
    {
        return array(
            'guid' => $this->guid,
            'runner' => $this->runner,
        );
    }

    /**
     * Returns guid.
     *
     * @return string $id
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Sets the guid
     *
     * @param string $guid
     *
     * @return void
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Returns the runner
     *
     * @return string $runner
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * Sets the runner
     *
     * @param string $runner
     *
     * @return void
     */
    public function setRunner($runner)
    {
        $this->runner = $runner;
    }
}
