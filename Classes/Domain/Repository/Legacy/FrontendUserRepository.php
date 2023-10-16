<?php

namespace CR\OfficialCleverreach\Domain\Repository\Legacy;

use CR\OfficialCleverreach\Domain\Repository\AbstractFrontendUserRepository;

/**
 * Class FrontendUserRepository
 * @package CR\OfficialCleverreach\Domain\Repository\Legacy
 */
class FrontendUserRepository extends AbstractFrontendUserRepository
{
    /**
     * Returns ids of users with valid email
     *
     * @return array
     */
    public function getAllIds()
    {
        $db = $this->getDatabaseConnection();
        $sourceUsers = $db->exec_SELECTgetRows(
            'uid, email, deleted',
            'fe_users',
            '',
            '',
            'uid ASC'
        );

        return $this->getValidIds($sourceUsers);
    }

    /**
     * @param int $groupId
     *
     * @return array of user ids
     */
    public function getUserIdsByGroupId($groupId)
    {
        $source = $this->getDatabaseConnection()->exec_SELECTgetRows('uid, usergroup', 'fe_users', '');

        return $this->filterByGroupId($groupId, $source);
    }

    /**
     * Return user ids filtered by page ids
     *
     * @param array $pageIds
     *
     * @return array
     */
    public function getUserIdsByPageIds($pageIds)
    {
        $results = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, pid',
            'fe_users',
            'pid in (' . implode(',', array_map('intval', $pageIds)) . ')'
        );

        return array_column($results, 'uid');
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public function getUserEmailById($userId)
    {
        $db = $this->getDatabaseConnection();
        $result = $db->exec_SELECTgetSingleRow('email', 'fe_users', 'uid=' . $db->fullQuoteStr($userId, 'fe_users'));

        return $result['email'];
    }

    /**
     * @param string|int $uid
     *
     * @return array|null
     */
    public function getUserById($uid)
    {
        $db = $this->getDatabaseConnection();

        return $db->exec_SELECTgetSingleRow('*', 'fe_users', 'uid=' . (int)$uid);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function getUsersByIds($ids)
    {
        $db = $this->getDatabaseConnection();
        $whereClause = 'uid in (' . implode(',', array_map('intval', $ids)) . ')';
        $results = $db->exec_SELECTgetRows('*', 'fe_users', $whereClause);

        return $results ?: [];
    }

    /**
     * Retrieves user by email.
     *
     * @param string $email
     *
     * @return array|FALSE|NULL
     */
    public function getUserByEmail($email)
    {
        $db = $this->getDatabaseConnection();

        return $db->exec_SELECTgetSingleRow(
            '*',
            'fe_users',
            'email=' . $db->fullQuoteStr($email, 'fe_users')
        );
    }

    /**
     * @param int $userId
     * @param int $status
     */
    public function setCrNewsletterSubscriptionStatus($userId, $status)
    {
        $db = $this->getDatabaseConnection();
        $db->exec_UPDATEquery('fe_users', "uid=$userId", ['cr_newsletter_subscription' => $status]);
    }

    /**
     * Updates `cr_newsletter_subscription` column to all users where field is not set
     *
     * @param int $configValue
     */
    public function setNewsletterStatusIfIsNull($configValue)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            'fe_users',
            'ISNULL(cr_newsletter_subscription)',
            ['cr_newsletter_subscription' => $configValue]
        );
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
