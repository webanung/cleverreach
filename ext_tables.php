<?php

use CR\OfficialCleverreach\Controller\CheckStatusController;
use CR\OfficialCleverreach\Controller\OfficialCleverreachController;
use CR\OfficialCleverreach\Controller\SupportController;

defined('TYPO3_MODE') || die('Access denied.');

if (TYPO3_MODE === 'BE') {
    /**
     * Register Backend Module
     */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'CR.OfficialCleverreach',
        'tools',
        'officialcleverreach',
        '',
        [
            OfficialCleverreachController::class => 'dashboard,initialSyncTask,welcome,tokenExpired,buildFirstEmail,retrySync',
            CheckStatusController::class => 'userInfo,initialSync',
            SupportController::class => 'support',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:cleverreach/ext_icon.png',
            'labels' => 'LLL:EXT:cleverreach/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}
