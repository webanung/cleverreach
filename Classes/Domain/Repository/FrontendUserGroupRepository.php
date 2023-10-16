<?php

namespace CR\OfficialCleverreach\Domain\Repository;

use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class FrontendUserGroupRepository
 * @package CR\OfficialCleverreach\Domain\Repository
 */
class FrontendUserGroupRepository implements FrontendUserGroupRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllUserGroups()
    {
        $queryBuilder = $this->getQueryBuilder();
        $userGroupFolders = $this->getBaseQuery()
            ->where($queryBuilder->expr()->neq('deleted', 1))
            ->execute()
            ->fetchAll();

        return array_column($userGroupFolders, 'title');
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    public function getUserGroupIdsByPageId($pageId)
    {
        $queryBuilder = $this->getQueryBuilder();

        return  $this->getBaseQuery()
            ->where($queryBuilder->expr()->eq('pid', (int)$pageId))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $ids = id1,id2,id3
     *
     * @return array
     */
    public function getGroupTitleByUid($ids)
    {
        $queryBuilder = $this->getQueryBuilder();
        $userGroups = $this->getBaseQuery()
            ->where($queryBuilder->expr()->neq('deleted', 1))
            ->andWhere($queryBuilder->expr()->in('uid', $ids))
            ->execute()
            ->fetchAll();

        return array_column($userGroups, 'title');
    }

    /**
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getBaseQuery()
    {
        return $this->getQueryBuilder()->select('uid', 'title', 'pid', 'deleted')->from('fe_groups');
    }

    /**
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
    }
}
