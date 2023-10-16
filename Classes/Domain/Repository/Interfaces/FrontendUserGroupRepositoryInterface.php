<?php

namespace CR\OfficialCleverreach\Domain\Repository\Interfaces;

/**
 * Interface FrontendUserGroupRepositoryInterface
 * @package CR\OfficialCleverreach\Domain\Repository\Interfaces
 */
interface FrontendUserGroupRepositoryInterface
{
    /**
     * @return array
     */
    public function getAllUserGroups();

    /**
     * @param int $pageId
     *
     * @return array
     */
    public function getUserGroupIdsByPageId($pageId);

    /**
     * @param string $ids = id1,id2,id3
     *
     * @return array
     */
    public function getGroupTitleByUid($ids);
}
