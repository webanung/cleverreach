<?php

namespace WebanUg\Cleverreach\Eid;

require_once __DIR__ . '/../autoload.php';

use WebanUg\Cleverreach\Exceptions\UnknownActionNameException;
use WebanUg\Cleverreach\Factory\FrontendControllerFactory;
use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FrontendRouterEid
 * @package WebanUg\Cleverreach\Eid
 */
class FrontendRouterEid
{
    /**
     * Handles incoming controller action.
     *
     * @throws \WebanUg\Cleverreach\Exceptions\UnknownActionNameException
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException
     */
    public function processRequest()
    {
        if (Helper::isCurrentVersion9OrHigher()) {
            $controllerName = $this->getController();
            $action = $this->getAction();
        } else {
            list($controllerName, $action) = Helper::getActionAndControllerName(GeneralUtility::_GET('action'));
        }

        /** @var \TYPO3\CMS\Extbase\Mvc\Controller\ActionController $controller */
        $controller = FrontendControllerFactory::create($controllerName);

        if (!method_exists($controller, $action)) {
            throw new UnknownActionNameException('Unknown action called!');
        }

        return $controller->$action();
    }

    /**
     * Returns controller name.
     *
     * @return string
     */
    private function getController()
    {
        return $this->getParam('controller');
    }

    /**
     * Returns controller action.
     *
     * @return string
     */
    private function getAction()
    {
        $action = $this->getParam('action');

        return $action . 'Action';
    }

    /**
     * Returns request parameter value based on its name.
     *
     * @param string $paramName
     *
     * @return string
     */
    private function getParam($paramName)
    {
        $params = GeneralUtility::_GET('tx_officialcleverreach_tools');

        return $params[$paramName];
    }
}
