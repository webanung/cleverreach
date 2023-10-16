<?php

namespace WebanUg\Cleverreach\IntegrationServices\Sync;

use CleverReach\BusinessLogic\Sync\BaseSyncTask;
use CleverReach\Infrastructure\ServiceRegister;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Class UpdateNewsletterStatusSyncTask
 * @package WebanUg\Cleverreach\IntegrationServices\Sync
 */
class UpdateNewsletterStatusSyncTask extends BaseSyncTask
{
    /** @var FrontendUserRepositoryInterface */
    private $userRepository;

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configValue = $this->getConfigValue();
        $this->reportProgress(10);
        $this->getUserRepository()->setNewsletterStatusIfIsNull($configValue);

        $this->reportProgress(100);
    }

    /**
     * @return \WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface
     */
    private function getUserRepository()
    {
        if ($this->userRepository === null) {
            $this->userRepository = ServiceRegister::getService(FrontendUserRepositoryInterface::class);
        }

        return $this->userRepository;
    }

    /**
     * @return int
     */
    private function getConfigValue()
    {
        if (Helper::isCurrentVersion9OrHigher()) {
            $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('official_cleverreach');
        } else {
            $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['official_cleverreach']);
        }

        if (array_key_exists('syncUsersAsSubscribers', $conf)) {
            return (int)$conf['syncUsersAsSubscribers'];
        }

        return 0;
    }
}
