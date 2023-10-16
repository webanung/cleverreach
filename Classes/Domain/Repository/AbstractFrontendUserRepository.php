<?php

namespace WebanUg\Cleverreach\Domain\Repository;

use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use WebanUg\Cleverreach\Utility\Helper;

/**
 * Class AbstractFrontendUserRepository
 * @package WebanUg\Cleverreach\Domain\Repository
 */
abstract class AbstractFrontendUserRepository implements FrontendUserRepositoryInterface
{
    /**
     * Returns ids of users with valid email
     *
     * @return array
     */
    abstract public function getAllIds();

    /**
     * @param int $groupId
     *
     * @return array of user ids
     */
    abstract public function getUserIdsByGroupId($groupId);

    /**
     * Return user ids filtered by page ids
     *
     * @param array $pageIds
     *
     * @return array
     */
    abstract public function getUserIdsByPageIds($pageIds);

    /**
     * @param int $userId
     *
     * @return string
     */
    abstract public function getUserEmailById($userId);

    /**
     * @param string|int $uid
     *
     * @return array|null
     */
    abstract public function getUserById($uid);

    /**
     * @param array $ids
     *
     * @return array
     */
    abstract public function getUsersByIds($ids);

    /**
     * Retrieves user by email.
     *
     * @param string $email
     *
     * @return array|FALSE|NULL
     */
    abstract public function getUserByEmail($email);

    /**
     * @param int $userId
     * @param int $status
     */
    abstract public function setCrNewsletterSubscriptionStatus($userId, $status);
    /**
     * @param array $sourceUsers
     *
     * @return array
     */
    protected function getValidIds($sourceUsers)
    {
        $validIds = [];
        foreach ($sourceUsers as $user) {
            $deleted = (int)Helper::getValueIfNotEmpty('deleted', $user);
            $validEmail = filter_var($user['email'], FILTER_VALIDATE_EMAIL);
            if ($validEmail && !$deleted) {
                $validIds[] = $user['uid'];
            }
        }

        return $validIds;
    }

    /**
     * @param int $groupId
     * @param array $source
     *
     * @return array
     */
    protected function filterByGroupId($groupId, $source)
    {
        $results = [];

        foreach ($source as $item) {
            $userGroups = explode(',', $item['usergroup']);
            if (in_array($groupId, $userGroups)) {
                $results[] = $item['uid'];
            }
        }

        return $results;
    }
}
