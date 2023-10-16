<?php

namespace WebanUg\Cleverreach\Domain\Repository\Interfaces;

/**
 * Interface PageRepositoryInterface
 * @package WebanUg\Cleverreach\Domain\Repository\Interfaces
 */
interface PageRepositoryInterface
{
    /**
     * Returns folders which contains users with its roots
     *
     * @return array
     */
    public function getAllUserFolders();

    /**
     * Returns ids of pages which have users filtered by its root id
     *
     * @param int $rootId
     *
     * @return array
     */
    public function getUserFolderIdsFilteredByRootId($rootId);

    /**
     * @param int $id
     *
     * @return array
     */
    public function getUserFolderNameWithItsRoot($id);

    /**
     * @param int $id
     *
     * @return array
     */
    public function fetchPageInfoById($id);

    /**
     * @return array
     */
    public function getUserFoldersIds();

    /**
     * @param array $filters
     * @param string $type
     *
     * @return array
     */
    public function getFilteredArticles($filters, $type);
}
