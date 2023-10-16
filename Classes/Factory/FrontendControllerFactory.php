<?php

namespace WebanUg\Cleverreach\Factory;

use WebanUg\Cleverreach\Controller\ArticleSearchController;
use WebanUg\Cleverreach\Controller\AsyncProcessController;
use WebanUg\Cleverreach\Controller\AuthController;
use WebanUg\Cleverreach\Controller\EventHandlerController;
use WebanUg\Cleverreach\Controller\ProductSearchController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;

/**
 * Class FrontendControllerFactory
 * @package WebanUg\Cleverreach\Factory
 */
class FrontendControllerFactory
{
    const CLEVERREACH_ASYNC = 'AsyncProcess';
    const CLEVERREACH_AUTH = 'Auth';
    const CLEVERREACH_SEARCH = 'ArticleSearch';
    const CLEVERREACH_WEBHOOK = 'EventHandler';
    const CLEVERREACH_PRODUCT_SEARCH = 'ProductSearch';

    /**
     * Instantiates a new controller based on its name.
     *
     * @param string $controllerName
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
     *
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException
     */
    public static function create($controllerName)
    {
        switch ($controllerName) {
            case self::CLEVERREACH_ASYNC:
                return GeneralUtility::makeInstance(ObjectManager::class)->get(AsyncProcessController::class);
            case self::CLEVERREACH_AUTH:
                return GeneralUtility::makeInstance(ObjectManager::class)->get(AuthController::class);
            case self::CLEVERREACH_SEARCH:
                return GeneralUtility::makeInstance(ObjectManager::class)->get(ArticleSearchController::class);
            case self::CLEVERREACH_WEBHOOK:
                return GeneralUtility::makeInstance(ObjectManager::class)->get(EventHandlerController::class);
            case self::CLEVERREACH_PRODUCT_SEARCH:
                return GeneralUtility::makeInstance(ObjectManager::class)->get(ProductSearchController::class);
            default:
                throw new UnknownClassException('Unknown controller called!');
        }
    }
}
