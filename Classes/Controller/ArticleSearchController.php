<?php

namespace CR\OfficialCleverreach\Controller;

use CR\OfficialCleverreach\Utility\Helper;
use CR\OfficialCleverreach\Utility\SchemaProvider;
use CR\OfficialCleverreach\Utility\SearchResultsProvider;
use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ArticleSearchController extends ActionController
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
     * Returns searchable items
     */
    public function getItemsAction()
    {
        Helper::dieJson($this->getSchemaProvider()->getSearchableItems()->toArray());
    }

    /**
     * Returns searchable item schema
     */
    public function getSchemaAction()
    {
        try {
            Helper::dieJson($this->getSchemaProvider()->getSchema(GeneralUtility::_GET('type'))->toArray());
        } catch (Exception $exception) {
            Helper::dieJson($this->errorResponse($exception->getMessage()), 400);
        }
    }

    /**
     * Returns search results
     */
    public function getSearchResultsAction()
    {
        $type = GeneralUtility::_GET('type');
        $searchableItems = array_column($this->getSchemaProvider()->getSearchableItems()->toArray(), 'code');
        if (!in_array($type, $searchableItems, true)) {
            Helper::dieJson($this->errorResponse('Unknown searchable item ' . $type), 400);
        }

        $filter = GeneralUtility::_GET('filter');
        if (empty($filter)) {
            Helper::dieJson([]);
        }

        try {
            Helper::dieJson($this->getSearchResultsProvider()->getSearchResults($type, $filter)->toArray());
        } catch (Exception $exception) {
            Helper::dieJson($this->errorResponse($exception->getMessage()), $exception->getCode());
        }
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

    /**
     * Returns error data
     *
     * @param string $message
     *
     * @return array
     */
    private function errorResponse($message)
    {
        return ['status' => 'error', 'message' => $message];
    }
}
