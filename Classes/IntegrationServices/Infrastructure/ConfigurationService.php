<?php

namespace WebanUg\Cleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationService extends Configuration
{
    const INTEGRATION_LIST_NAME = 'TYPO3 CMS';

    public function isProductSearchEnabled()
    {
        return true;
    }

    public function getProductSearchParameters()
    {
        $baseEidUrl = GeneralUtility::locationHeaderUrl('/index.php?eID=cleverreach_frontend');

        $url = $baseEidUrl . (Helper::isCurrentVersion9OrHigher()
                ? Helper::buildQueryStringForVersion9OrLater('ProductSearch', 'search')
                : Helper::buildQueryStringForVersion8OrPrevious(Helper::CLEVERREACH_PRODUCT_SEARCH));

        $name = self::INTEGRATION_LIST_NAME .
            ' (' . $this->getSiteName() . ') '
            . Helper::getTranslation('tx_officialcleverreach_article_search', 'Article Search')
            . ' - '
            . GeneralUtility::locationHeaderUrl('/');

        return [
            'name' => $name,
            'url' => $url,
            'password' => $this->getProductSearchEndpointPassword()
        ];
    }

    /**
     * Retrieves integration name
     *
     * @return string
     */
    public function getIntegrationName()
    {
        return str_replace(' ', '', self::INTEGRATION_LIST_NAME);
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return 'TYPO3-Default';
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return 'ReHyBISsKe';
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return 'L08l90mXhcWiNwPfVUH0TzfB7QjZj4e4';
    }

    /**
     * @return string
     */
    public function getIntegrationListName()
    {
        return self::INTEGRATION_LIST_NAME;
    }

    /**
     * @return string
     */
    public function getSiteName()
    {
        return $this->getConfigRepository()->get('TYPO3_SITE_NAME');
    }

    /**
     * Saves Typo3 site name to configuration table
     */
    public function saveSiteName()
    {
        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $this->getConfigRepository()->set('TYPO3_SITE_NAME', $siteName);
    }

    /**
     * Saves backend language to config  table
     *
     * @param string $language
     */
    public function saveUserLanguage($language)
    {
        $this->getConfigRepository()->set('TYPO3_USER_LANGUAGE', $language);
    }

    /**
     * Returns user backend language
     *
     * @return string
     */
    public function getUserLanguage()
    {
        return $this->getConfigRepository()->get('TYPO3_USER_LANGUAGE');
    }

    /**
     * @inheritdoc
     */
    public function getCrEventHandlerURL()
    {
        $callbackUrl = GeneralUtility::locationHeaderUrl('/index.php?eID=cleverreach_frontend');

        return $callbackUrl . (Helper::isCurrentVersion9OrHigher()
            ? Helper::buildQueryStringForVersion9OrLater('EventHandler', 'handleWebhooks')
            : Helper::buildQueryStringForVersion8OrPrevious(Helper::CLEVERREACH_WEBHOOK));
    }

    public function getPluginUrl()
    {
        return GeneralUtility::locationHeaderUrl('/typo3');
    }

    /**
     * Not supported
     * @return string
     */
    public function getNotificationMessage()
    {
        return '';
    }
}
