<?php
/**
 * Table configuration fe_users
 */
$feUsersColumns = [
    'cr_newsletter_subscription' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:cleverreach/Resources/Private/Language/locallang.xlf:cr_newsletter_subscription',
        'config' => [
            'type' => 'check',
            'default' => 0,
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_users',
    'cr_newsletter_subscription',
    '',
    'after:image'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $feUsersColumns);
