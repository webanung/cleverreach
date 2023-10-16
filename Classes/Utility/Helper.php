<?php

namespace WebanUg\Cleverreach\Utility;

use WebanUg\Cleverreach\Exceptions\UnknownActionNameException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Helper
{
    const CLEVERREACH_ASYNC = 'cleverreach_async';
    const CLEVERREACH_AUTH = 'cleverreach_auth';
    const CLEVERREACH_ITEMS = 'cleverreach_items';
    const CLEVERREACH_SCHEMA = 'cleverreach_schema';
    const CLEVERREACH_SEARCH = 'cleverreach_search';
    const CLEVERREACH_WEBHOOK = 'cleverreach_webhook';
    const CLEVERREACH_PRODUCT_SEARCH = 'cleverreach_product_search';

    /**
     * Returns oauth2 redirect uri
     *
     * @param bool $refreshTokens
     *
     * @return string
     */
    public static function getCallbackUri($refreshTokens)
    {
        $callbackUrl = GeneralUtility::locationHeaderUrl(
            '/index.php?eID=cleverreach_frontend' . ($refreshTokens ? '&refresh=1' : '')
        );

        return $callbackUrl . (self::isCurrentVersion9OrHigher()
                ? self::buildQueryStringForVersion9OrLater('Auth', 'oauth2callback')
                : self::buildQueryStringForVersion8OrPrevious(self::CLEVERREACH_AUTH));
    }

    /**
     * @param string $controller
     * @param string $action
     *
     * @return string
     */
    public static function buildQueryStringForVersion9OrLater($controller, $action)
    {
        $params['tx_officialcleverreach_tools']['controller'] = $controller;
        $params['tx_officialcleverreach_tools']['action'] = $action;

        return HttpUtility::buildQueryString($params, '&');
    }

    /**
     * Builds query for eID controller on versions 8.9.9 and previous
     *
     * @param string $action
     * @param string $prependCharacter
     *
     * @return string
     */
    public static function buildQueryStringForVersion8OrPrevious($action, $prependCharacter = '&')
    {
        $queryString = http_build_query(['action' => $action], '', '&', PHP_QUERY_RFC3986);

        return $prependCharacter . $queryString;
    }

    /**
     * Return true if current version is 9.0.0 or higher
     *
     * @return bool
     */
    public static function isCurrentVersion9OrHigher()
    {
        return version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0.0', 'ge');
    }

    /**
     * @param string $key
     * @param array $map
     *
     * @return string
     */
    public static function getValueIfNotEmpty($key, $map)
    {
        if (!empty($map[$key])) {
            return $map[$key];
        }

        return '';
    }

    /**
     * Returns translated string for a given key.
     *
     * @param string $key
     * @param string $fallback
     * @param string $lang
     *
     * @return string|null
     */
    public static function getTranslation($key, $fallback, $lang = null)
    {
        try {
            if ($lang !== null) {
                return LocalizationUtility::translate($key, 'official_cleverreach', null, $lang);
            }

            return LocalizationUtility::translate($key, 'official_cleverreach');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Sets response header content type to json, echos supplied $data as json and terminates the process
     *
     * @param array $data Array to be encoded to json response.
     * @param int $statusCode
     */
    public static function dieJson($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        die(json_encode($data));
    }

    /**
     * @param string $message
     * @param string $statusHeader
     */
    public static function diePlain($message = '', $statusHeader = 'HTTP/1.1 200 OK')
    {
        header($statusHeader);

        die($message);
    }

    /**
     * @param string $actionName
     *
     * @return array
     * @throws \WebanUg\Cleverreach\Exceptions\UnknownActionNameException
     */
    public static function getActionAndControllerName($actionName)
    {
        switch ($actionName) {
            case self::CLEVERREACH_ASYNC:
                return ['AsyncProcess', 'runAction'];
            case self::CLEVERREACH_AUTH:
                return ['Auth', 'oauth2callbackAction'];
            case self::CLEVERREACH_ITEMS:
                return ['ArticleSearch', 'getItemsAction'];
            case self::CLEVERREACH_SCHEMA:
                return ['ArticleSearch', 'getSchemaAction'];
            case self::CLEVERREACH_SEARCH:
                return ['ArticleSearch', 'getSearchResultsAction'];
            case self::CLEVERREACH_WEBHOOK:
                return['EventHandler', 'handleWebhooksAction'];
            case self::CLEVERREACH_PRODUCT_SEARCH:
                return['ProductSearch', 'searchAction'];
            default:
                throw new UnknownActionNameException('Unknown action called!');
        }
    }

    /**
     * Returns backend user data in base64 encoded json format
     *
     * @return string
     */
    public static function getUserData()
    {
        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        list($firstName, $lastName) = self::getFirstAndLastName($backendUser->user);
        $userData = [
            'email' => self::getValueIfNotEmpty('email', $backendUser->user),
            'company' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'firstname' => $firstName,
            'lastname' => $lastName,
            'gender' => '',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => '',
        ];

        return base64_encode(json_encode($userData));
    }

    /**
     * @param array $backendUser
     *
     * @return array
     */
    private static function getFirstAndLastName($backendUser)
    {
        $nameParts = explode(
            ' ',
            preg_replace('!\s+!', ' ', self::getValueIfNotEmpty('realName', $backendUser))
        );

        return [array_shift($nameParts), trim(implode(' ', $nameParts))];
    }

    /**
     * Get the current language
     *
     * @return string
     */
    public static function getLanguage()
    {
        return !empty($GLOBALS['BE_USER']->uc['lang']) ? $GLOBALS['BE_USER']->uc['lang'] : 'en';
    }

    /**
     * @param string $iso3Code
     *
     * @return string
     */
    public static function getCountryNameByIso3($iso3Code)
    {
        try {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var \SJBR\StaticInfoTables\Domain\Repository\CountryRepository $countryRepository */
            $countryRepository = $objectManager->get('SJBR\\StaticInfoTables\\Domain\\Repository\\CountryRepository');
            $countries = $countryRepository->findAllowedByIsoCodeA3($iso3Code);
            if (empty($countries)) {
                return '';
            }

            /** @var \SJBR\StaticInfoTables\Domain\Model\Country $country */
            $country = array_shift($countries);

            return $country->getShortNameEn();
        } catch (UnknownObjectException $exception) {
            return '';
        }
    }
}