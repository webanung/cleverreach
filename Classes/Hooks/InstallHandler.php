<?php

namespace WebanUg\Cleverreach\Hooks;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\BusinessLogic\Sync\ExchangeAccessTokenTask;
use CleverReach\BusinessLogic\Sync\ProductSearchSyncTask;
use CleverReach\BusinessLogic\Sync\RegisterEventHandlerTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use WebanUg\Cleverreach\Utility\Helper;
use WebanUg\Cleverreach\Utility\Initializer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class InstallHandler
 * @package WebanUg\Cleverreach\Hooks
 */
class InstallHandler
{
    const CONFIG_TABLE = 'tx_officialcleverreach_domain_model_configuration';
    const PROCESS_TABLE = 'tx_officialcleverreach_domain_model_process';
    const QUEUE_TABLE = 'tx_officialcleverreach_domain_model_queue';
    const STATISTIC_DISPLAYED_CONFIG_KEY = 'CLEVERREACH_IMPORT_STATISTICS_DISPLAYED';

    /**
     * Executes uninstall actions.
     *
     * @param AfterPackageDeactivationEvent $event
     */
    public function uninstall(AfterPackageDeactivationEvent $event)
    {
        if ($event->getPackageKey() === 'official_cleverreach') {
            $this->removeEventHandlerEndpoint();
            if (Helper::isCurrentVersion9OrHigher()) {
                $this->truncateTablesForTYPO3Version9OrLater();
            } else {
                $this->truncateTablesForTYPO3Version8OrPrevious();
            }
        }
    }

    /**
     * Executes install actions.
     *
     * WARNING: Any installation action must be implemented using TYPO3 DB utility, and NOT by using core services!!!
     *          This does not apply to update actions, since they are performed when data mappings have already been
     *          initialized.
     *
     * @param AfterPackageActivationEvent $event
     * @return void
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function install(AfterPackageActivationEvent $event)
    {
        if ($event->getPackageKey() !== 'official_cleverreach') {
            return;
        }

        $connection = $this->getDBConnection(self::CONFIG_TABLE);
        $taskRunnerStatus = Helper::isCurrentVersion9OrHigher()
            ? $this->getTaskRunnerStatusVersion9AndLater($connection)
            : $this->getTaskRunnerStatusVersion8AndPrevious($connection);

        if (empty($taskRunnerStatus)) {
            $this->insertDefaultValuesIntoConfigTable($connection);
        }

        $productSearchPassword = Helper::isCurrentVersion9OrHigher()
            ? $this->getProductSearchPassword9AndLater($connection)
            : $this->getProductSearchPasswordVersion8AndPrevious($connection);

        if (empty($productSearchPassword)) {
            $this->insertProductSearchPassword($connection);
        }

        $this->update110();
        $this->update130();
    }

    /**
     * Removes event handler endpoint.
     */
    private function removeEventHandlerEndpoint()
    {
        require_once __DIR__ . '/../../Resources/Private/vendor/autoload.php';
        Initializer::initialize();

        /** @var \CleverReach\BusinessLogic\Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        try {
            $proxy->deleteReceiverEvent();
            $proxy->deleteProductSearchEndpoint($configService->getProductSearchContentId());
        } catch (\Exception $e) {
            Logger::logError('Could not remove event handler because: ' . $e->getMessage(), 'Integration');
        }
    }

    /**
     * Executes actions for updating to version 1.1.0.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function update110()
    {
        $initialSyncTaskItem = $this->getInitialSyncItem();
        if (!empty($initialSyncTaskItem)) {
            $this->setImportStatisticsDisplayed();
            try {
                $this->enqueueUpdate110Tasks();
            } catch (\CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException $e) {
                Logger::logError($e->getMessage(), 'Integration');
            }
        }
    }

    /**
     * Preforms v1.3.0 migration
     */
    private function update130()
    {
        $initialSyncTaskItem = $this->getInitialSyncItem();
        if (!empty($initialSyncTaskItem)) {
            try {
                $this->enqueueUpdate130Tasks();
            } catch (\CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException $e) {
                Logger::logError($e->getMessage(), 'Integration');
            }
        }
    }

    /**
     * Returns InitialSync item
     *
     * @return array
     */
    private function getInitialSyncItem()
    {
        $connection = $this->getDBConnection('tx_officialcleverreach_domain_model_queue');

        return Helper::isCurrentVersion9OrHigher()
            ? $this->getInitialSyncItemVersion9AndLater($connection)
            : $this->getInitialSyncItemVersion8AndPrevious($connection);
    }

    /**
     * Sets import statistics to have been displayed in case when plugin has been
     * updated from a version in which initial sync has already been completed.
     */
    private function setImportStatisticsDisplayed()
    {
        $connection = $this->getDBConnection(static::CONFIG_TABLE);

        $displayedFlag = Helper::isCurrentVersion9OrHigher()
            ? $this->getDisplayedFlagVersion9AndLater($connection)
            : $this->getDisplayedFlagVersion8AndPrevious($connection);

        if (empty($displayedFlag)) {
            $values = [
                'cr_key' => self::STATISTIC_DISPLAYED_CONFIG_KEY,
                'cr_value' => '1',
            ];

            if (Helper::isCurrentVersion9OrHigher()) {
                $connection->insert(self::CONFIG_TABLE, $values);
            } else {
                $connection->exec_INSERTquery(self::CONFIG_TABLE, $values);
            }
        }
    }

    /**
     * Enqueues tasks for update to version 1.1.0, namely exchange access token task and register event handler task.
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function enqueueUpdate110Tasks()
    {
        require_once __DIR__ . '/../../Resources/Private/vendor/autoload.php';
        Initializer::initialize();
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);

        /** @var ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $exchangeTokenTask = $queue->findLatestByType('ExchangeAccessTokenTask');
        $eventHandlerTask = $queue->findLatestByType('RegisterEventHandlerTask');

        if ($exchangeTokenTask === null) {
            $queue->enqueue($configService->getQueueName(), new ExchangeAccessTokenTask());
        }

        if ($eventHandlerTask === null) {
            $queue->enqueue($configService->getQueueName(), new RegisterEventHandlerTask());
        }
    }

    /**
     * Enqueues tasks for update to version 1.3.0 (ProductSearchSyncTask).
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function enqueueUpdate130Tasks()
    {
        require_once __DIR__ . '/../../Resources/Private/vendor/autoload.php';
        Initializer::initialize();
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);

        /** @var ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $productSearchTask = $queue->findLatestByType(ProductSearchSyncTask::getClassName());

        if ($productSearchTask === null) {
            $queue->enqueue($configService->getQueueName(), new ProductSearchSyncTask());
        }
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $connection
     *
     * @return array|NULL
     */
    private function getInitialSyncItemVersion8AndPrevious($connection)
    {
        $quotedType = $connection->fullQuoteStr('InitialSyncTask', self::QUEUE_TABLE);
        $quotedStatus = $connection->fullQuoteStr('completed', self::QUEUE_TABLE);

        $initialSyncTaskItem = $connection->exec_SELECTgetRows(
            '*',
            self::QUEUE_TABLE,
            'type=' . $quotedType . ' AND status=' . $quotedStatus
        );

        return $initialSyncTaskItem;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     *
     * @return array|NULL
     */
    private function getInitialSyncItemVersion9AndLater($connection)
    {
        $queryBuilder = $connection->createQueryBuilder();
        $expression = $queryBuilder->expr();

        return $queryBuilder
            ->select('*')
            ->from(self::QUEUE_TABLE)
            ->where($expression->eq('type', $queryBuilder->quote('InitialSyncTask')))
            ->andWhere($expression->eq('status', $queryBuilder->quote('completed')))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $connection
     *
     * @return mixed
     */
    private function getDisplayedFlagVersion8AndPrevious($connection)
    {
        $displayedFlag = $connection->exec_SELECTgetRows(
            '*',
            self::CONFIG_TABLE,
            'cr_key=' . $connection->fullQuoteStr(self::STATISTIC_DISPLAYED_CONFIG_KEY, self::CONFIG_TABLE)
        );

        return $displayedFlag;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     *
     * @return mixed
     */
    private function getDisplayedFlagVersion9AndLater($connection)
    {
        $queryBuilder = $connection->createQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from(self::CONFIG_TABLE)
            ->where($queryBuilder->expr()
                ->eq('cr_key', $queryBuilder->quote(self::STATISTIC_DISPLAYED_CONFIG_KEY)))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $connection
     *
     * @return mixed
     */
    private function getTaskRunnerStatusVersion8AndPrevious($connection)
    {
        $taskRunnerStatus = $connection->exec_SELECTgetRows(
            '*',
            self::CONFIG_TABLE,
            'cr_key=' . $connection->fullQuoteStr('CLEVERREACH_TASK_RUNNER_STATUS', self::CONFIG_TABLE)
        );

        return $taskRunnerStatus;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection $connection
     *
     * @return mixed
     */
    private function getProductSearchPasswordVersion8AndPrevious($connection)
    {
        $taskRunnerStatus = $connection->exec_SELECTgetRows(
            '*',
            self::CONFIG_TABLE,
            'cr_key=' . $connection->fullQuoteStr('CLEVERREACH_PRODUCT_SEARCH_PASSWORD', self::CONFIG_TABLE)
        );

        return $taskRunnerStatus;
    }

    /**
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     *
     * @return mixed
     */
    private function getTaskRunnerStatusVersion9AndLater($connection)
    {
        $queryBuilder = $connection->createQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from(self::CONFIG_TABLE)
            ->where($queryBuilder->expr()->eq('cr_key', $queryBuilder->quote('CLEVERREACH_TASK_RUNNER_STATUS')))
            ->execute()
            ->fetchAll();
    }
/**
     * @param \TYPO3\CMS\Core\Database\Connection $connection
     *
     * @return mixed
     */
    private function getProductSearchPassword9AndLater($connection)
    {
        $queryBuilder = $connection->createQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from(self::CONFIG_TABLE)
            ->where($queryBuilder->expr()->eq('cr_key', $queryBuilder->quote('CLEVERREACH_PRODUCT_SEARCH_PASSWORD')))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection | \TYPO3\CMS\Core\Database\Connection $connection $connection
     */
    private function insertDefaultValuesIntoConfigTable($connection)
    {
        $values = [
            'cr_key' => 'CLEVERREACH_TASK_RUNNER_STATUS',
            'cr_value' => json_encode(['guid' => '', 'timestamp' => null]),
        ];

        if (Helper::isCurrentVersion9OrHigher()) {
            $connection->insert(self::CONFIG_TABLE, $values);
        } else {
            $connection->exec_INSERTquery(self::CONFIG_TABLE, $values);
        }
    }

    /**
     * @param \TYPO3\CMS\Core\Database\DatabaseConnection | \TYPO3\CMS\Core\Database\Connection $connection $connection
     */
    private function insertProductSearchPassword($connection)
    {
        $values = [
            'cr_key' => 'CLEVERREACH_PRODUCT_SEARCH_PASSWORD',
            'cr_value' => md5(time()),
        ];

        if (Helper::isCurrentVersion9OrHigher()) {
            $connection->insert(self::CONFIG_TABLE, $values);
        } else {
            $connection->exec_INSERTquery(self::CONFIG_TABLE, $values);
        }
    }

    /**
     * Truncates all CleverReach specific tables for TYPO3 V9 or later
     */
    private function truncateTablesForTYPO3Version9OrLater()
    {
        $this->getDBConnection(self::QUEUE_TABLE)->truncate(self::QUEUE_TABLE);
        $this->getDBConnection(self::PROCESS_TABLE)->truncate(self::PROCESS_TABLE);
        $this->getDBConnection(self::CONFIG_TABLE)->truncate(self::CONFIG_TABLE);
    }

    /**
     * Truncates all CleverReach specific tables for TYPO3 V8 or previous
     */
    private function truncateTablesForTYPO3Version8OrPrevious()
    {
        $connection = $this->getDBConnection();
        $connection->exec_TRUNCATEquery(self::QUEUE_TABLE);
        $connection->exec_TRUNCATEquery(self::PROCESS_TABLE);
        $connection->exec_TRUNCATEquery(self::CONFIG_TABLE);
    }

    /**
     * @param null $tableName
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection | \TYPO3\CMS\Core\Database\Connection $connection
     */
    private function getDBConnection($tableName = null)
    {
        return Helper::isCurrentVersion9OrHigher()
            ? GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)
            : $GLOBALS['TYPO3_DB'];
    }
}
