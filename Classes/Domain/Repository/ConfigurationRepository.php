<?php

namespace WebanUg\Cleverreach\Domain\Repository;

use WebanUg\Cleverreach\Domain\Model\Configuration;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\ConfigRepositoryInterface;
use Doctrine\DBAL\FetchMode;

/**
 * Class ConfigurationRepository
 * @package WebanUg\Cleverreach\Domain\Repository
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
        $row = $this->getConfigItem($key);

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
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder
            ->update(static::TABLE_NAME)
            ->set('cr_value', $configItem->getValue())
            ->where($queryBuilder->expr()->eq('cr_key', $queryBuilder->createNamedParameter($configItem->getKey())))
            ->execute();
    }

    /**
     * Returns configuration item as an associative representation of table row, if it exists.
     *
     * @param string $key
     *
     * @return mixed
     */
    private function getConfigItem($key)
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from(static::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('cr_key', $queryBuilder->createNamedParameter($key)))
            ->execute()
            ->fetch(FetchMode::ASSOCIATIVE);
    }


}
