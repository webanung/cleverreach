<?php

namespace CleverReach\BusinessLogic;

use CleverReach\BusinessLogic\Scheduler\Interfaces\ScheduleRepositoryInterface;
use CleverReach\BusinessLogic\Scheduler\Models\Schedule;
use CleverReach\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use CleverReach\Infrastructure\ORM\RepositoryRegistry as InfrastructureRepositoryRegistry;

/**
 * Class RepositoryRegistry
 *
 * @package CleverReach\BusinessLogic
 */
class RepositoryRegistry extends InfrastructureRepositoryRegistry
{
    /**
     * Returns schedule repository
     *
     * @return ScheduleRepositoryInterface
     *
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public static function getScheduleRepository()
    {
        /** @var ScheduleRepositoryInterface $repository */
        $repository = static::getRepository(Schedule::getClassName());
        if (!($repository instanceof ScheduleRepositoryInterface)) {
            throw new RepositoryClassException('Instance class is not implementation of ScheduleRepositoryInterface');
        }

        return $repository;
    }
}
