<?php

namespace CR\OfficialCleverreach\Utility;

use CleverReach\BusinessLogic\Utility\ArticleSearch\FilterParser;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\AuthorAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\DateAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\HtmlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ImageAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\NumberAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Validator;
use CleverReach\Infrastructure\ServiceRegister;
use CR\OfficialCleverreach\Domain\Repository\AbstractPageRepository;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SearchResultsProvider
{
    /**
     * @var SchemaProvider
     */
    private $schemaProvider;
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @param string $contentType
     * @param string $rawFilter
     *
     * @return SearchResult
     *
     * @throws \CR\OfficialCleverreach\Exceptions\ContentTypeNotFoundException
     * @throws \CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching
     * @throws \Exception
     */
    public function getSearchResults($contentType, $rawFilter)
    {
        $searchResult = new SearchResult();
        $articles = $this->getFilteredArticles($contentType, $rawFilter);

        if (empty($articles)) {
            return $searchResult;
        }

        foreach ($articles as $article) {
            $this->createArticle($contentType, $article, $searchResult);
        }

        return $searchResult;
    }

    /**
     * @param string $contentType
     * @param array $article
     * @param SearchResult $searchResult
     *
     * @throws \Exception
     */
    private function createArticle($contentType, $article, SearchResult $searchResult)
    {
        if ($contentType === 'news') {
            $specificAttributes = $this->getNewsAttributes($article);
        } else {
            $specificAttributes = $this->getPageAndBlogAttributes($article, $contentType);
        }

        $attributes = array_merge($specificAttributes, $this->getCommonAttributes($article));
        $createDate = new \DateTime('@' . $article['crdate']);

        $searchResult->addSearchResultItem(
            new SearchResultItem(
                $contentType,
                $article['uid'],
                $article['title'],
                $createDate,
                $attributes
            )
        );
    }

    /**
     * @param array $article
     *
     * @return array
     */
    private function getCommonAttributes($article)
    {
        $htmlBlocks = $this->formatBlocks($article, AbstractPageRepository::HTML_BLOCKS);
        $textBlocks = $this->formatBlocks($article, AbstractPageRepository::TEXT_BLOCKS);
        $images = $this->getImages($article);
        $tags = [];
        foreach ($article['tags'] as $tag) {
            $tags[] = new TextAttribute('tag', $tag['title']);
        }

        $categories = [];
        foreach ($article['categories'] as $category) {
            $categories[] = new TextAttribute('category', $category['title']);
        }

        return [
            new NumberAttribute('id', $article['uid']),
            new TextAttribute('title', $article['title']),
            new ImageAttribute('main_image', $this->getMainImage($article)),
            new SimpleCollectionAttribute('tags', $tags),
            new SimpleCollectionAttribute('categories', $categories),
            new SimpleCollectionAttribute('html_blocks', $htmlBlocks),
            new SimpleCollectionAttribute('text_blocks', $textBlocks),
            new SimpleCollectionAttribute('images', $images),
        ];
    }

    /**
     * @param array $article
     * @param string $contentType
     *
     * @return array
     * @throws \Exception
     */
    private function getPageAndBlogAttributes($article, $contentType)
    {
        $lastUpdate = new \DateTime('@' . $article['lastUpdated']);

        $attributes = [
            new DateAttribute('update_date', $lastUpdate),
            new NumberAttribute('id', $article['uid']),
            new TextAttribute('title', $article['title']),
            new TextAttribute('subtitle', $article['subtitle']),
            new TextAttribute('abstract', $article['abstract']),
            new UrlAttribute('url', GeneralUtility::locationHeaderUrl('/index.php?id=' . $article['uid'])),
        ];

        if ($contentType === 'page') {
            $attributes[] = new AuthorAttribute('author', $article['author']);
            $attributes[] = new HtmlAttribute('full_article', $this->getFullPageHtml($article));
        } else {
            $authors = [];
            foreach ($article['authors'] as $author) {
                $authors[] = new AuthorAttribute('author', $author['name']);
            }

            $attributes[] = new SimpleCollectionAttribute('authors', $authors);
        }

        return $attributes;
    }

    /**
     * @param array $article
     *
     * @return array
     */
    private function getNewsAttributes($article)
    {
        return [
            new TextAttribute('teaser', $article['teaser']),
            new AuthorAttribute('author', $article['author']),
            new TextAttribute('content', $article['bodytext']),
            new UrlAttribute('url', GeneralUtility::locationHeaderUrl('/index.php?id=' . $article['pid'])),
        ];
    }

    /**
     * @param string $contentType
     * @param string $rawFilter
     *
     * @return array
     *
     * @throws \CR\OfficialCleverreach\Exceptions\ContentTypeNotFoundException
     * @throws \CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching
     */
    private function getFilteredArticles($contentType, $rawFilter)
    {
        $schema = $this->getSchemaProvider()->getSchema($contentType);

        $filterParser = new FilterParser();
        $filters = $filterParser->generateFilters($contentType, null, urlencode($rawFilter));

        $filterValidator = new Validator();
        $filterValidator->validateFilters($filters, $schema);

        $filterBy = [];
        foreach ($filters as $filter) {
            $fieldCode = $filter->getAttributeCode();
            $fieldValue = $filter->getAttributeValue();
            $condition = $filter->getCondition();
            if ($date = \DateTime::createFromFormat(
                'Y-m-d\TH:i:s.u\Z',
                $filter->getAttributeValue(),
                new \DateTimeZone('UTC')
            )) {
                $fieldValue = $date->getTimestamp();
            }

            $filterBy[] = ['field' => $fieldCode, 'value' => $fieldValue, 'condition' => $condition];
        }

        return $this->getPageRepository()->getFilteredArticles($filterBy, $contentType);
    }

    /**
     * @return SchemaProvider
     */
    private function getSchemaProvider()
    {
        if ($this->schemaProvider === null) {
            $this->schemaProvider = new SchemaProvider();
        }

        return $this->schemaProvider;
    }

    /**
     * @return PageRepositoryInterface
     */
    private function getPageRepository()
    {
        if ($this->pageRepository === null) {
            $this->pageRepository = ServiceRegister::getService(PageRepositoryInterface::class);
        }

        return $this->pageRepository;
    }

    /**
     * @param array $article
     * @param string $type (html or text)
     *
     * @return SimpleAttribute[]
     */
    private function formatBlocks($article, $type)
    {
        $blocks = [];
        foreach ($article[$type] as $sourceBlock) {
            $block = new TextAttribute($type, $sourceBlock['bodytext']);
            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * @param array $article
     *
     * @return string
     */
    private function getFullPageHtml($article)
    {
        $blocks = '';
        foreach ($article[AbstractPageRepository::HTML_BLOCKS] as $htmlBlocks) {
            $blocks .= ' ' . $htmlBlocks['bodytext'];
        }

        return $blocks;
    }

    /**
     * @param array $article
     *
     * @return array
     */
    private function getImages($article)
    {
        $images = [];
        foreach ($article['images'] as $image) {
            $images[] = new SimpleCollectionAttribute(
                'image',
                [
                    new TextAttribute('name', $image['name']),
                    new ImageAttribute(
                        'image',
                        GeneralUtility::locationHeaderUrl('/fileadmin' . $image['identifier'])
                    ),
                ]
            );
        }

        return $images;
    }

    /**
     * @param array $article
     *
     * @return string
     */
    private function getMainImage($article)
    {
        $mainImageUrl = '';
        if (!empty($article['images'][0]['identifier'])) {
            $mainImageUrl = GeneralUtility::locationHeaderUrl('/fileadmin' . $article['images'][0]['identifier']);
        }

        return $mainImageUrl;
    }
}
