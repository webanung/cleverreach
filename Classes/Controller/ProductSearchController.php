<?php

namespace WebanUg\Cleverreach\Controller;

use WebanUg\Cleverreach\Utility\Helper;
use WebanUg\Cleverreach\Utility\SchemaProvider;
use WebanUg\Cleverreach\Utility\SearchResultsProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ProductSearchController extends ActionController
{
    /**
     * @var SchemaProvider
     */
    private $schemaProvider;
    /**
     * @var SearchResultsProvider
     */
    private $searchResultsProvider;

    /**
     * CleverReach Product search endpoint
     *
     * @throws \WebanUg\Cleverreach\Exceptions\ContentTypeNotFoundException
     * @throws \CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching
     */
    public function searchAction()
    {
        $action = GeneralUtility::_GP('get');
        if ($action === 'search') {
            $results = $this->getResultsAction();
        } else {
            $results = $this->getFiltersAction();
        }

        Helper::dieJson($results);
    }

    /**
     * Returns search filters, used to search for data.
     *
     * @return array
     */
    private function getFiltersAction()
    {
        return [
            [
                'name' => 'Type',
                'description' => 'Article type',
                'required' => true,
                'query_key' => 'type',
                'type' => 'dropdown',
                'values' => $this->getTypesForSearch(),
            ],
            [
                'name' => 'Title',
                'description' => '',
                'required' => true,
                'query_key' => 'title',
                'type' => 'input',
            ],
        ];
    }

    /**
     * Returns items for provided filters
     *
     * @throws \WebanUg\Cleverreach\Exceptions\ContentTypeNotFoundException
     * @throws \CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching
     */
    private function getResultsAction()
    {
        $type = GeneralUtility::_GP('type');
        $title = GeneralUtility::_GP('title');

        $rawFilter = "title ct $title";
        $items = $this->getSearchResultsProvider()->getSearchResults($type, $rawFilter)->toArray();

        $results = [
            'settings' => [
                'type' => 'content',
                'link_editable' => false,
                'link_text_editable' => true,
                'image_size_editable' => true,
            ],
            'items' => $this->formatItemsForProductSearch($items),
        ];

        return $results;
    }

    /**
     * Formats items to CleverReach format
     *
     * @param array $items
     *
     * @return array
     */
    private function formatItemsForProductSearch(array $items)
    {
        $formattedItems = [];
        foreach ($items as $searchableItem) {
            $formattedItem = [
                'title'       => $searchableItem['attributes']['title'],
                'description' => $searchableItem['attributes']['subtitle'],
                'content'     => $this->buildContent($searchableItem['attributes']),
                'image'       => $searchableItem['attributes']['main_image'],
                'url'         => $searchableItem['attributes']['url'],
            ];

            $formattedItems[] = $formattedItem;
        }

        return $formattedItems;
    }

    /**
     * Returns searchable items filter
     *
     * @return array
     */
    private function getTypesForSearch()
    {
        $results = [];
        $items = $this->getSchemaProvider()->getSearchableItems()->toArray();
        foreach ($items as $item) {
            $results[] = [
                'text' => $item['name'],
                'value' => $item['code'],
            ];
        }

        return $results;
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    private function buildContent(array $attributes)
    {
        $content = '<h1>' . $attributes['title'] . '</h1>';

        if (!empty($attributes['subtitle'])) {
            $content .= '<h2>' . $attributes['subtitle'] . '</h2>';
        }

        $content .= implode(' ', $attributes['html_blocks']);

        return '<!--#html #-->' . $content . '<!--#/html#-->';
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
     * @return SearchResultsProvider
     */
    private function getSearchResultsProvider()
    {
        if ($this->searchResultsProvider === null) {
            $this->searchResultsProvider = new SearchResultsProvider();
        }

        return $this->searchResultsProvider;
    }
}
