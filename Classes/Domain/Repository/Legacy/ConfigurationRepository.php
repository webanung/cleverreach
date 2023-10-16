<?php

namespace CR\OfficialCleverreach\Domain\Repository\Legacy;

use CR\OfficialCleverreach\Domain\Model\Configuration;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\ConfigRepositoryInterface;

/**
 * Class ConfigurationRepository
 * @package CR\OfficialCleverreach\Domain\Repository\Legacy
 */
class ConfigurationRepository extends AbstractRepository implements ConfigRepositoryInterface
{
    const TABLE_NAME = 'tx_officialcleverreach_domain_model_configuration';

    /**
     * Returns configuration value.
     *
     * @param string $key
     *
     * @return Configuration
     */
    public function get($key)
    {
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'cr_key, cr_value',
            static::TABLE_NAME,
            'cr_key="' . $key . '"'
        );

        if (empty($row)) {
            return null;
        }

        return Configuration::fromArray($row);
    }

    /**
     * Saves configuration value.
     *
     * @param Configuration $configItem
     */
    public function save(Configuration $configItem)
    {
        if ($this->get($configItem->getKey()) === null) {
            $this->insert($configItem);
        } else {
            $this->update($configItem);
        }
    }

    /**
     * Updates configuration item.
     *
     * @param Configuration $configItem
     */
    private function update(Configuration $configItem)
    {
        $this->getDatabaseConnection()->exec_UPDATEquery(
            static::TABLE_NAME,
            'cr_key="' . $configItem->getKey() . '"',
            ['cr_value' => $configItem->getValue()]
        );
    }
}
