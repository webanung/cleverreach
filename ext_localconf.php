<?php

const EXTENSION_KEY = 'official_cleverreach';

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'CR.OfficialCleverreach',
    'tools',
    [
        'AsyncProcess' => 'run',
        'ArticleSearch' => 'getItems, getSchema, getSearchResults',
        'ProductSearch' => 'search',
        'Auth' => 'oauth2callback',
        'EventHandler' => 'handleWebhooks',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['cleverreach_frontend'] = \CR\OfficialCleverreach\Eid\FrontendRouterEid::class . '::processRequest';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['extendingTCA'][] = $_EXTKEY;

// hook register
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \CR\OfficialCleverreach\Hooks\HookHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \CR\OfficialCleverreach\Hooks\HookHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] = \CR\OfficialCleverreach\Hooks\HookHandler::class;


$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:cms/locallang_tca.xlf'][] = 'EXT:' . EXTENSION_KEY . '/Resources/Private/Language/locallang.xlf';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['de']['EXT:cms/locallang_tca.xlf'][] = 'EXT:' . EXTENSION_KEY . '/Resources/Private/Language/de.locallang.xlf';
