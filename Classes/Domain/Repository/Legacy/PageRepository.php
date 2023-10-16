<?php

namespace WebanUg\Cleverreach\Domain\Repository\Legacy;

use WebanUg\Cleverreach\Domain\Repository\AbstractPageRepository;

class PageRepository extends AbstractPageRepository
{
    /**
     * @param int $id
     *
     * @return array
     */
    public function fetchPageInfoById($id)
    {
        return $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid, pid, title',
            self::PAGE_TABLE,
            'uid=' . (int)$id
        );
    }

    /**
     * @return array
     */
    public function getUserFoldersIds()
    {
        $sourceIds = $this->getDatabaseConnection()->exec_SELECTgetRows('pid', self::USERS_TABLE, 'deleted=0', 'pid');

        return empty($sourceIds) ? [] : array_column($sourceIds, 'pid');
    }

    /**
     * @param array $filters
     *
     * @return array|mixed|NULL
     */
    protected function getSourceNews($filters)
    {
        $whereClause = $this->buildWhereClauseForPagesAndNews($filters);

        return $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, pid, crdate, title, author, author_email, teaser, title, bodytext',
            self::NEWS_TABLE,
            $whereClause
        );
    }

    /**
     * @param $filters
     *
     * @return array|mixed|NULL
     */
    protected function getSourcePages($filters)
    {
        $whereClause = $this->buildWhereClauseForPagesAndNews($filters);
        $pages = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, doktype, subtitle, title, author, author_email, abstract, crdate, lastUpdated',
            self::PAGE_TABLE,
            $whereClause
        );

        return $pages;
    }

    /**
     * @param int $pageId
     *
     * @return array|NULL
     */
    protected function getSourceContentBlocks($pageId)
    {
        $db = $this->getDatabaseConnection();
        $results = $db->exec_SELECTgetRows(
            'header, bodytext, uid',
            'tt_content',
            'deleted = 0 
            AND bodytext IS NOT NULL 
            AND RTRIM(bodytext) != \'\'
            AND pid = ' . $db->fullQuoteStr($pageId, 'tt_content')
        );

        return $results;
    }

    /**
     * @param bool|\mysqli_result|object $res MySQLi result object / DBAL object $res
     *
     * @return array
     */
    private function fetchResults($res)
    {
        $output = [];
        while ($record = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $output[] = $record;
        }

        $this->getDatabaseConnection()->sql_free_result($res);

        return $output;
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getImages($pageId)
    {
        $res = $this->getDatabaseConnection()->exec_SELECT_mm_query(
            'uid_foreign, identifier, name',
            'sys_file',
            'sys_file_reference',
            '',
            'and deleted = 0 and sys_file_reference.pid = ' . $pageId
        );

        return $this->fetchResults($res);
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getNewsTags($pageId)
    {
        $res = $this->getDatabaseConnection()->exec_SELECT_mm_query(
            self::NEWS_TAG_TABLE . 'title',
            self::NEWS_TABLE,
            self::NEWS_TAG_MM_TABLE,
            self::NEWS_TAG_TABLE,
            'and ' . self::NEWS_TAG_TABLE . 'deleted = 0 and ' . self::NEWS_TAG_MM_TABLE . 'uid_local = ' . (int)$pageId
        );

        return $this->fetchResults($res);
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getBlogTags($pageId)
    {
        $res = $this->getDatabaseConnection()->exec_SELECT_mm_query(
            'tx_blog_domain_model_tag.title',
            'tx_blog_domain_model_tag',
            'tx_blog_tag_pages_mm',
            self::PAGE_TABLE,
            'and tx_blog_domain_model_tag.deleted = 0 and tx_blog_tag_pages_mm.uid_foreign = ' . (int)$pageId
        );

        return $this->fetchResults($res);
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getBlogAuthors($pageId)
    {
        $res = $this->getDatabaseConnection()->exec_SELECT_mm_query(
            'tx_blog_domain_model_author.name',
            'pages',
            'tx_blog_post_author_mm',
            'tx_blog_domain_model_author',
            'and tx_blog_domain_model_author.deleted = 0 and tx_blog_post_author_mm.uid_local = ' . (int)$pageId
        );

        return $this->fetchResults($res);
    }

    /**
     * @param int $pageId
     * @param string $tableName
     *
     * @return array
     */
    protected function getCategories($pageId, $tableName)
    {
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECT_mm_query(
            'sys_category.title',
            'sys_category',
            'sys_category_record_mm',
            $tableName,
            'and sys_category.deleted = 0 and sys_category_record_mm.tablenames=' .
            $db->fullQuoteStr($tableName, 'sys_category_record_mm') .
            ' and sys_category_record_mm.uid_foreign = ' . (int)$pageId
        );

        return $this->fetchResults($res);
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getPageTags($pageId)
    {
        $pageTags = [];
        $db = $this->getDatabaseConnection();
        $newsIds = $db->exec_SELECTgetRows('uid', self::NEWS_TABLE, 'pid=' . (int)$pageId);
        if (empty($newsIds)) {
            return [];
        }

        foreach (array_column($newsIds, 'uid') as $newsId) {
            $pageTags[] = $this->getNewsTags($newsId);
        }

        return call_user_func_array('array_merge', $pageTags);
    }

    /**
     * @param string $tableName
     * @param string $value
     *
     * @return string
     */
    protected function quoteValue($tableName, $value)
    {
        return $this->getDatabaseConnection()->fullQuoteStr($value, $tableName);
    }

    /**
     * This method is overridden because since version 8.7 this method doesn't exist
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
