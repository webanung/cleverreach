<?php

namespace CR\OfficialCleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CR\OfficialCleverreach\Domain\Model\Configuration;
use CR\OfficialCleverreach\Domain\Repository\ConfigurationRepository;
use CR\OfficialCleverreach\Domain\Repository\Legacy\ConfigurationRepository as ConfigurationLegacyRepository;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ConfigRepositoryService implements ConfigRepositoryInterface
{
    /**
     * @var ConfigurationRepository
     */
    private $configRepository;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $configItem = $this->getConfigRepository()->get($key);

        return $configItem ? $configItem->getValue() : null;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $configItem = new Configuration($key, $value);

        $this->getConfigRepository()->save($configItem);

        return true;
    }

    /**
     * @return ConfigurationRepository
     */
    private function getConfigRepository()
    {
        if ($this->configRepository === null) {
            $configRepositoryClass = Helper::isCurrentVersion9OrHigher()
                ? ConfigurationRepository::class
                : ConfigurationLegacyRepository::class;

            $this->configRepository = GeneralUtility::makeInstance(ObjectManager::class)
                ->get($configRepositoryClass);
        }

        return $this->configRepository;
    }
}
