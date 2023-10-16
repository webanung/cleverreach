<?php

namespace WebanUg\Cleverreach\Domain\Repository;

use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FrontendUserRepository
 * @package WebanUg\Cleverreach\Domain\Repository
 */
class FrontendUserRepository extends AbstractFrontendUserRepository
{
    /**
     * @return array
     */
    public function getAllIds()
    {
        return $this->getValidIds($this->getAllUsers());
    }

    /**
     * @param int $groupId
     *
     * @return array
     */
    public function getUserIdsByGroupId($groupId)
    {
        return $this->filterByGroupId($groupId, $this->getAllUsers());
    }

    /**
     * @param array $pageIds
     *
     * @return array
     */
    public function getUserIdsByPageIds($pageIds)
    {
        if (empty($pageIds)) {
            return [];
        }

        $queryBuilder = $this->getQueryBuilder();
        $results = $queryBuilder->select('uid', 'pid')
            ->from('fe_users')
            ->where($queryBuilder->expr()->in('pid', $pageIds))
            ->execute()
            ->fetchAll();

        return array_column($results, 'uid');
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public function getUserEmailById($userId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $sourceUser = $queryBuilder->select('uid', 'email')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', (int)$userId))
            ->execute()
            ->fetch();

        return Helper::getValueIfNotEmpty('email', $sourceUser);
    }

    /**
     * @param int|string $uid
     *
     * @return array|null
     */
    public function getUserById($uid)
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('uid', (int)$uid))
            ->execute()
            ->fetch();
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function getUsersByIds($ids)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        $results = $queryBuilder->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->in('uid', $ids))
            ->execute()
            ->fetchAll();

        return $results ?: [];
    }

    /**
     * @param string $email
     *
     * @return array|FALSE|NULL
     */
    public function getUserByEmail($email)
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder->select('*')
            ->from('fe_users')
            ->where($queryBuilder->expr()->eq('email', $queryBuilder->quote($email)))
            ->execute()
            ->fetch();
    }

    /**
     * @param int $userId
     * @param int $status
     */
    public function setCrNewsletterSubscriptionStatus($userId, $status)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->update('fe_users')
            ->set('cr_newsletter_subscription', $status)
            ->where($queryBuilder->expr()->eq('uid', (int)$userId))
            ->execute();
    }

    /**
     * Updates `cr_newsletter_subscription` column to all users where field is not set
     *
     * @param int $configValue
     */
    public function setNewsletterStatusIfIsNull($configValue)
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->update('fe_users')
            ->set('cr_newsletter_subscription', $configValue)
            ->where($queryBuilder->expr()->isNull('cr_newsletter_subscription'))
            ->execute();
    }

    /**
     * Retrieves all frontend users from system
     *
     * @return array
     */
    private function getAllUsers()
    {
        return $this->getQueryBuilder()
            ->select('uid', 'email', 'deleted', 'usergroup')
            ->from('fe_users')
            ->orderBy('uid', 'ASC')
            ->execute()
            ->fetchAll();
    }

    /**
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
    }
}
