<?php

namespace CleverReach\BusinessLogic\Interfaces;

use CleverReach\BusinessLogic\Entity\Notification;

/**
 * Interface Notifications
 *
 * @package CleverReach\BusinessLogic\Interfaces
 */
interface Notifications
{
    const CLASS_NAME = __CLASS__;

    /**
     * Creates a new notification in system integration.
     *
     * @param Notification $notification Notification object that contains info such as
     *   identifier, name, date, description, url.
     *
     * @return boolean
     */
    public function push(Notification $notification);
}
