<?php

namespace WebanUg\Cleverreach\Domain\Repository\Interfaces;

/**
 * Interface FrontendUserRepositoryInterface
 * @package WebanUg\Cleverreach\Domain\Repository\Interfaces
 */
interface FrontendUserRepositoryInterface
{
    /**
     * Returns ids of users with valid email
     *
     * @return array
     */
    public function getAllIds();

    /**
     * @param int $groupId
     *
     * @return array of user ids
     */
    public function getUserIdsByGroupId($groupId);

    /**
     * Return user ids filtered by page ids
     *
     * @param array $pageIds
     *
     * @return array
     */
    public function getUserIdsByPageIds($pageIds);

    /**
     * @param int $userId
     *
     * @return string
     */
    public function getUserEmailById($userId);

    /**
     * @param string|int $uid
     *
     * @return array|null
     */
    public function getUserById($uid);

    /**
     * @param array $ids
     *
     * @return array
     */
    public function getUsersByIds($ids);

    /**
     * Retrieves user by email.
     *
     * @param string $email
     *
     * @return array|FALSE|NULL
     */
    public function getUserByEmail($email);

    /**
     * Modifies newsletter status to user with given uid
     *
     * @param int $userId
     * @param int $status
     */
    public function setCrNewsletterSubscriptionStatus($userId, $status);

    /**
     * Updates `cr_newsletter_subscription` column to all users where field is not set
     *
     * @param int $configValue
     */
    public function setNewsletterStatusIfIsNull($configValue);
}
