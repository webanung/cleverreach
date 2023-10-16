<?php

namespace CR\OfficialCleverreach\Domain\Repository;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Operators;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractPageRepository
 * @package CR\OfficialCleverreach\Domain\Repository
 */
abstract class AbstractPageRepository implements PageRepositoryInterface
{
    const TEXT_BLOCKS = 'text_blocks';
    const HTML_BLOCKS = 'html_blocks';

    const PAGE_TABLE = 'pages';
    const USERS_TABLE = 'fe_users';
    const NEWS_TABLE = 'tx_news_domain_model_news';
    const NEWS_TAG_TABLE = 'tx_news_domain_model_tag';
    const NEWS_TAG_MM_TABLE = 'tx_news_domain_model_news_tag_mm';

    /**
     * @var array
     */
    protected static $CONDITION_MAPPING = [
        Conditions::EQUALS => '=',
        Conditions::NOT_EQUAL => '<>',
        Conditions::GREATER_THAN => '>',
        Conditions::LESS_THAN => '<',
        Conditions::LESS_EQUAL => '<=',
        Conditions::GREATER_EQUAL => '>=',
        Conditions::CONTAINS => 'LIKE',
    ];
    /**
     * Field specific mapping.
     *
     * @var array
     */
    protected static $FIELD_MAPPING = [
        'id' => 'uid',
        'date' => 'crdate',
        'itemCode' => 'doktype',
    ];
    /**
     * Specific values mapping
     *
     * @var array
     */
    protected static $VALUE_MAPPING = [
        'page' => 1,
        'blog_post' => 137,
    ];

    /**
     * @param int $id
     *
     * @return array
     */
    abstract public function fetchPageInfoById($id);

    /**
     * @return array
     */
    abstract public function getUserFoldersIds();

    /**
     * Returns folders which contains users with its roots
     *
     * @return array
     */
    public function getAllUserFolders()
    {
        $userFoldersIds = $this->getUserFoldersIds();
        $folders = [];
        foreach ($userFoldersIds as $foldersId) {
            $folders[] = $this->getUserFolderNameWithItsRoot($foldersId);
        }

        return empty($folders) ? [] : array_unique(call_user_func_array('array_merge', $folders));
    }

    /**
     * Returns ids of pages which have users filtered by its root id
     *
     * @param int $rootId
     *
     * @return array
     */
    public function getUserFolderIdsFilteredByRootId($rootId)
    {
        $rootFolderInfo = $this->fetchPageInfoById($rootId);
        $userFoldersIds = $this->getUserFoldersIds();
        $results = [];
        foreach ($userFoldersIds as $userFolderId) {
            $folderNameWithRoot = $this->getUserFolderNameWithItsRoot($userFolderId);
            if (in_array($rootFolderInfo['title'], $folderNameWithRoot, true)) {
                $results[] = $userFolderId;
            }
        }

        return $results;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getUserFolderNameWithItsRoot($id)
    {
        $data = $this->fetchPageInfoById($id);

        if (empty($data['title'])) {
            return [];
        }

        $userFolders[] = $data['title'];
        $parent = $this->fetchPageInfoById($data['pid']);
        if (empty($parent)) {
            return $userFolders;
        }

        while ((int)$parent['pid'] !== 0) {
            $parent = $this->fetchPageInfoById($parent['pid']);
        }

        $userFolders[] = $parent['title'];

        return $userFolders;
    }

    /**
     * @param array $filters
     * @param string $type
     *
     * @return array
     */
    public function getFilteredArticles($filters, $type)
    {
        $filters = array_column($filters, null, 'field');
        if ($type === 'news') {
            return $this->getNews($filters);
        }

        return $this->getPages($filters);
    }

    /**
     * @param string $tableName
     * @param string $value
     *
     * @return string
     */
    abstract protected function quoteValue($tableName, $value);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getBlogTags($pageId);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getBlogAuthors($pageId);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getPageTags($pageId);

    /**
     * @param array $filters
     *
     * @return mixed
     */
    abstract protected function getSourcePages($filters);

    /**
     * @param array $filters
     *
     * @return mixed
     */
    abstract protected function getSourceNews($filters);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getNewsTags($pageId);

    /**
     * @param int $pageId
     * @param string $tableName
     *
     * @return array
     */
    abstract protected function getCategories($pageId, $tableName);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getSourceContentBlocks($pageId);

    /**
     * @param int $pageId
     *
     * @return array
     */
    abstract protected function getImages($pageId);

    /**
     * @param array $filters
     *
     * @return array
     */
    protected function getNews($filters)
    {
        $news = $this->getSourceNews($filters);
        $newsFormatted = $this->formatItems($filters, $news, self::NEWS_TABLE);

        return $this->filterResults($filters, $newsFormatted, 'news');
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    protected function getPages($filters)
    {
        $pages = $this->getSourcePages($filters);
        $pagesFormatted = $this->formatItems($filters, $pages, self::PAGE_TABLE);

        return $this->filterResults($filters, $pagesFormatted);
    }

    /**
     * Formats items based on page type
     * Categories and tags are always fetch by uid, other required attributes depends on page type
     *  - pages and blogs => uid
     *  - news => pid
     *
     * @param array $filters
     * @param array $items
     * @param string $tableName
     *
     * @return array
     */
    private function formatItems($filters, $items, $tableName)
    {
        $itemIdIndex = $tableName === self::PAGE_TABLE ? 'uid' : 'pid';
        $isBlogPost = !empty($filters['itemCode']['value']) && $filters['itemCode']['value'] === 'blog_post';

        $itemsFormatted = [];

        foreach ($items as $item) {
            $item[self::HTML_BLOCKS] = $this->getContentBlocksHTML($item[$itemIdIndex]);
            if (array_key_exists(self::HTML_BLOCKS, $filters)
                && !$this->satisfyBlockFilter($filters, $item[self::HTML_BLOCKS], self::HTML_BLOCKS)
            ) {
                continue;
            }

            $item[self::TEXT_BLOCKS] = $this->getContentBlocksText($item[$itemIdIndex]);
            if (array_key_exists(self::TEXT_BLOCKS, $filters)
                && !$this->satisfyBlockFilter($filters, $item[self::TEXT_BLOCKS], self::TEXT_BLOCKS)
            ) {
                continue;
            }

            $item['images'] = $this->getImages($item[$itemIdIndex]);
            $item['categories'] = $this->getCategories($item['uid'], $tableName);
            $this->setItemTags($item, $tableName, $isBlogPost);

            $itemsFormatted[] = $item;
        }

        return $itemsFormatted;
    }

    /**
     * @param array $item
     * @param string $tableName
     * @param bool $isBlogPost
     */
    private function setItemTags(&$item, $tableName, $isBlogPost)
    {
        if ($tableName === self::PAGE_TABLE) {
            if ($isBlogPost) {
                $item['authors'] = $this->getBlogAuthors($item['uid']);
                $item['tags'] = $this->getBlogTags($item['uid']);
            } else {
                $item['tags'] = $this->getPageTags($item['uid']);
            }
        } else {
            $item['tags'] = $this->getNewsTags($item['uid']);
        }
    }

    /**
     * @param array $filters
     *
     * @return mixed
     */
    protected function buildWhereClauseForPagesAndNews($filters)
    {
        $whereClause = 'deleted = 0';
        $notAllowedFields = [self::HTML_BLOCKS, self::TEXT_BLOCKS, 'authors', 'tags', 'categories'];
        foreach ($filters as $filter) {
            if (($filter['field'] === 'itemCode' && $filter['value'] === 'news')
                || in_array($filter['field'], $notAllowedFields, true)
            ) {
                continue;
            }

            $field = array_key_exists($filter['field'], self::$FIELD_MAPPING) ?
                self::$FIELD_MAPPING[$filter['field']] : $filter['field'];
            $value = array_key_exists($filter['value'], self::$VALUE_MAPPING) ?
                self::$VALUE_MAPPING[$filter['value']] : $filter['value'];
            $condition = array_key_exists($filter['condition'], self::$CONDITION_MAPPING) ?
                self::$CONDITION_MAPPING[$filter['condition']] : $filter['condition'];

            if ($condition === 'LIKE') {
                $value = "%$value%";
            }

            $value = $this->quoteValue(self::PAGE_TABLE, $value);
            $whereClause .= ' ' . Operators::AND_OPERATOR . ' ' . $field . ' ' . $condition . ' ' . $value;
        }

        return $whereClause;
    }

    /**
     * @param int $pageId
     *
     * @return array|NULL
     */
    protected function getContentBlocksText($pageId)
    {
        $results = $this->getSourceContentBlocks($pageId);

        foreach ($results as &$content) {
            $content['bodytext'] = strip_tags($content['bodytext']);
        }

        return $results;
    }

    /**
     * @param int $pageId
     *
     * @return array|NULL
     */
    protected function getContentBlocksHTML($pageId)
    {
        $results = $this->getSourceContentBlocks($pageId);
        $images = $this->getImages($pageId);

        foreach ($results as &$content) {
            $html = '<h2>' . $content['header'] . '</h2>';
            $html .= $content['bodytext'];

            foreach ($images as $image) {
                if ($content['uid'] === $image['uid_foreign']) {
                    $url = GeneralUtility::locationHeaderUrl('/fileadmin' . $image['identifier']);
                    $html .= "<br /><img src='{$url}' alt='{$content['header']}'/><br />";
                }
            }

            $content['bodytext'] = $html;
        }

        return $results;
    }

    /**
     * @param array $filters
     * @param array $itemsFormatted
     * @param string $type
     *
     * @return array
     */
    protected function filterResults($filters, $itemsFormatted, $type = 'pages')
    {
        if (array_key_exists('authors', $filters) && $type === 'pages') {
            $itemsFormatted = $this->filterByCollectionAttribute($filters, $itemsFormatted, 'authors', 'name');
        }

        if (array_key_exists('tags', $filters)) {
            $itemsFormatted = $this->filterByCollectionAttribute($filters, $itemsFormatted, 'tags', 'title');
        }

        if (array_key_exists('categories', $filters)) {
            $itemsFormatted = $this->filterByCollectionAttribute($filters, $itemsFormatted, 'categories', 'title');
        }

        return $itemsFormatted;
    }

    /**
     * @param array $filters
     * @param array $itemsFormatted
     * @param string $type
     * @param string $column
     *
     * @return array
     */
    protected function filterByCollectionAttribute($filters, $itemsFormatted, $type, $column)
    {
        $filterValue = $filters[$type]['value'];
        $filteredItems = [];
        foreach ($itemsFormatted as $item) {
            if (in_array($filterValue, array_column($item[$type], $column), true)) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * @param array $filters
     * @param array $blocks
     * @param string $type
     *
     * @return bool
     */
    protected function satisfyBlockFilter($filters, $blocks, $type)
    {
        $filterValue = $filters[$type]['value'];
        foreach (array_column($blocks, 'bodytext') as $block) {
            if (strpos($block, $filterValue) !== false) {
                return true;
            }
        }

        return false;
    }
}
