<?php

namespace CR\OfficialCleverreach\Domain\Repository\Legacy;

use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;

/**
 * Class FrontendUserGroupRepository
 * @package CR\OfficialCleverreach\Domain\Repository\Legacy
 */
class FrontendUserGroupRepository implements FrontendUserGroupRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllUserGroups()
    {
        $db = $this->getDatabaseConnection();
        $userGroupFolders = $db->exec_SELECTgetRows('title', 'fe_groups', 'deleted != 1');

        return array_column($userGroupFolders, 'title');
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    public function getUserGroupIdsByPageId($pageId)
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, title, pid',
            'fe_groups',
            'pid=' . (int)$pageId
        );
    }

    /**
     * @param string $ids = id1,id2,id3
     *
     * @return array
     */
    public function getGroupTitleByUid($ids)
    {
        $db = $this->getDatabaseConnection();
        $whereInClause = $this->getWhereInClause($ids);
        $whereClause = 'deleted != 1 AND ' . $whereInClause;
        $userGroup = $db->exec_SELECTgetRows('title', 'fe_groups', $whereClause);

        return array_column($userGroup, 'title');
    }

    /**
     * @param string $ids
     *
     * @return string
     */
    private function getWhereInClause($ids)
    {
        $idsArray = explode(',', $ids);

        return 'uid in (' . implode(',', array_map('intval', $idsArray)) . ')';
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
