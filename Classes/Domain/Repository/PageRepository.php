<?php

namespace WebanUg\Cleverreach\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageRepository
 * @package WebanUg\Cleverreach\Domain\Repository
 */
class PageRepository extends AbstractPageRepository
{
    /**
     * @param int $id
     *
     * @return array
     */
    public function fetchPageInfoById($id)
    {
        $queryBuilder = $this->getQueryBuilder(self::PAGE_TABLE);

        return $queryBuilder->select('uid', 'pid', 'title')
            ->from(self::PAGE_TABLE)
            ->where($queryBuilder->expr()->eq('uid', (int)$id))
            ->execute()
            ->fetch();
    }

    /**
     * @return array
     */
    public function getUserFoldersIds()
    {
        $sourceIds = $this->getQueryBuilder(self::USERS_TABLE)
            ->select('pid')
            ->from(self::USERS_TABLE)
            ->groupBy('pid')
            ->execute()
            ->fetchAll();

        return empty($sourceIds) ? [] : array_column($sourceIds, 'pid');
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getBlogTags($pageId)
    {
        $queryBuilder = $this->getQueryBuilder('tx_blog_domain_model_tag');

        $res = $queryBuilder->select('blog_tags.title')
            ->from('tx_blog_domain_model_tag', 'blog_tags')
            ->join('blog_tags', 'tx_blog_tag_pages_mm', 'blog_tag_pages_mm',
                'blog_tags.uid=blog_tag_pages_mm.uid_local')
            ->where($queryBuilder->expr()->eq('blog_tag_pages_mm.uid_foreign', (int)$pageId))
            ->execute()
            ->fetchAll();

        return !empty($res) ? $res : [];
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getBlogAuthors($pageId)
    {
        $queryBuilder = $this->getQueryBuilder('tx_blog_domain_model_author');

        $res = $queryBuilder->select('authors.name')
            ->from('tx_blog_domain_model_author', 'authors')
            ->join('authors', 'tx_blog_post_author_mm', 'blog_authors_mm', 'authors.uid=blog_authors_mm.uid_foreign')
            ->where($queryBuilder->expr()->eq('blog_authors_mm.uid_local', (int)$pageId))
            ->execute()
            ->fetchAll();

        return !empty($res) ? $res : [];
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getPageTags($pageId)
    {
        if (!ExtensionManagementUtility::isLoaded('news')) {
            return  [];
        }

        $pageTags = [];

        $queryBuilder = $this->getQueryBuilder(self::NEWS_TABLE);
        $newsIds = $queryBuilder->select('uid', 'pid')
            ->from(self::NEWS_TABLE)
            ->where($queryBuilder->expr()->eq('pid', (int)$pageId))
            ->execute()
            ->fetchAll();

        if (empty($newsIds)) {
            return [];
        }

        foreach (array_column($newsIds, 'uid') as $newsId) {
            $pageTags[] = $this->getNewsTags($newsId);
        }

        /** @noinspection PhpLanguageLevelInspection This file is used only when php5.6+ is active. */
        return array_merge(...$pageTags);
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    protected function getSourceNews($filters)
    {
        return $this->getQueryBuilder(self::NEWS_TABLE)
            ->select('uid', 'pid', 'crdate', 'title', 'author', 'author_email', 'teaser', 'bodytext')
            ->from(self::NEWS_TABLE)
            ->where($this->buildWhereClauseForPagesAndNews($filters))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param array $filters
     *
     * @return mixed
     */
    protected function getSourcePages($filters)
    {
        return $this->getQueryBuilder(self::PAGE_TABLE)
            ->select('uid', 'doktype', 'subtitle', 'title', 'author', 'author_email', 'abstract', 'crdate', 'lastUpdated')
            ->from(self::PAGE_TABLE)
            ->where($this->buildWhereClauseForPagesAndNews($filters))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getNewsTags($pageId)
    {
        $queryBuilder = $this->getQueryBuilder(self::NEWS_TAG_TABLE);

        $res = $queryBuilder->select('tags.title')
            ->from(self::NEWS_TAG_TABLE, 'tags')
            ->join('tags', self::NEWS_TAG_MM_TABLE, 'tags_news_mm', 'tags.uid=tags_news_mm.uid_foreign')
            ->where($queryBuilder->expr()->eq('tags_news_mm.uid_local', (int)$pageId))
            ->execute()
            ->fetchAll();

        return !empty($res) ? $res : [];
    }

    /**
     * @param int $pageId
     * @param string $tableName
     *
     * @return array
     */
    public function getCategories($pageId, $tableName)
    {
        $queryBuilder = $this->getQueryBuilder('sys_category');
        $expression = $queryBuilder->expr();
        $res = $queryBuilder->select('categories.title')
            ->from('sys_category', 'categories')
            ->join('categories', 'sys_category_record_mm', 'categories_mm', 'categories.uid=categories_mm.uid_local')
            ->where($expression->eq('categories_mm.tablenames', $queryBuilder->quote($tableName)))
            ->andWhere($expression->eq('categories_mm.uid_foreign', (int)$pageId))
            ->execute()
            ->fetchAll();

        return !empty($res) ? $res : [];
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getSourceContentBlocks($pageId)
    {
        $queryBuilder = $this->getQueryBuilder('tt_content');
        $expression = $queryBuilder->expr();

        return $queryBuilder->select('header', 'bodytext', 'uid')
            ->from('tt_content')
            ->where($expression->isNotNull('bodytext'))
            ->andWhere('RTRIM(bodytext) != \'\'')
            ->andWhere($expression->eq('pid', (int)$pageId))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param int $pageId
     *
     * @return array
     */
    protected function getImages($pageId)
    {
        $queryBuilder = $this->getQueryBuilder('sys_file_reference');

        return $queryBuilder->select('files_pages.uid_foreign', 'files.identifier', 'files.name')
            ->from('sys_file', 'files')
            ->join('files', 'sys_file_reference', 'files_pages', 'files_pages.uid_local=files.uid')
            ->where($queryBuilder->expr()->eq('files_pages.pid', (int)$pageId))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tableName
     * @param string $value
     *
     * @return string
     */
    protected function quoteValue($tableName, $value)
    {
        return $this->getQueryBuilder($tableName)->quote($value);
    }

    /**
     * @param string $tableName
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    private function getQueryBuilder($tableName)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
    }
}
