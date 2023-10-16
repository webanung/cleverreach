<?php

namespace CR\OfficialCleverreach\Utility;

use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Proxy;
use CleverReach\BusinessLogic\Proxy\AuthProxy;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeUpInterface;
use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\TaskRunner;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatusStorage;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerWakeup;
use CleverReach\Infrastructure\Utility\GuidProvider;
use CleverReach\Infrastructure\Utility\NativeSerializer;
use CleverReach\Infrastructure\Utility\Serializer;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CR\OfficialCleverreach\Domain\Repository\FrontendUserGroupRepository;
use CR\OfficialCleverreach\Domain\Repository\FrontendUserRepository;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use CR\OfficialCleverreach\Domain\Repository\Legacy\FrontendUserGroupRepository as FrontendUserGroupRepositoryLegacy;
use CR\OfficialCleverreach\Domain\Repository\Legacy\FrontendUserRepository as FrontendUserRepositoryLegacy;
use CR\OfficialCleverreach\Domain\Repository\Legacy\PageRepository as PageRepositoryLegacy;
use CR\OfficialCleverreach\Domain\Repository\PageRepository;
use CR\OfficialCleverreach\IntegrationServices\Business\AttributesService;
use CR\OfficialCleverreach\IntegrationServices\Business\RecipientsService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\AsyncProcessStarterService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigRepositoryService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\HttpClientService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\LoggerService;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\TaskQueueStorageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Initializer
 * @package CR\OfficialCleverreach\Utility
 */
class Initializer
{
    /**
     * Register all services
     */
    public static function initialize()
    {
        try {
            /** ********* Default CORE services ****************** */
            ServiceRegister::registerService(
                TimeProvider::CLASS_NAME,
                function () {
                    return new TimeProvider();
                }
            );

            ServiceRegister::registerService(
                Queue::CLASS_NAME,
                function () {
                    return new Queue();
                }
            );

            ServiceRegister::registerService(
                ProxyInterface::CLASS_NAME,
                function () {
                    return new Proxy();
                }
            );

            ServiceRegister::registerService(
                AuthProxy::CLASS_NAME,
                function () {
                    return new AuthProxy();
                }
            );

            ServiceRegister::registerService(
                Serializer::CLASS_NAME,
                function () {
                    return new NativeSerializer();
                }
            );

            ServiceRegister::registerService(
                TaskRunnerWakeUpInterface::CLASS_NAME,
                function () {
                    return new TaskRunnerWakeup();
                }
            );

            ServiceRegister::registerService(
                TaskRunner::CLASS_NAME,
                function () {
                    return new TaskRunner();
                }
            );

            ServiceRegister::registerService(
                GuidProvider::CLASS_NAME,
                function () {
                    return new GuidProvider();
                }
            );

            ServiceRegister::registerService(
                TaskRunnerStatusStorageInterface::CLASS_NAME,
                function () {
                    return new TaskRunnerStatusStorage();
                }
            );
            ServiceRegister::registerService(
                DefaultLoggerAdapter::CLASS_NAME,
                function () {
                    return new DefaultLogger();
                }
            );

            /** ********* Implemented services ****************** */
            ServiceRegister::registerService(
                Configuration::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(ConfigurationService::class);
                }
            );

            ServiceRegister::registerService(
                HttpClient::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(HttpClientService::class);
                }
            );

            ServiceRegister::registerService(
                ShopLoggerAdapter::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(LoggerService::class);
                }
            );

            ServiceRegister::registerService(
                AsyncProcessStarter::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(AsyncProcessStarterService::class);
                }
            );

            ServiceRegister::registerService(
                TaskQueueStorage::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(TaskQueueStorageService::class);
                }
            );

            ServiceRegister::registerService(
                Attributes::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(AttributesService::class);
                }
            );

            ServiceRegister::registerService(
                Recipients::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(RecipientsService::class);
                }
            );

            ServiceRegister::registerService(
                ConfigRepositoryInterface::CLASS_NAME,
                function () {
                    return GeneralUtility::makeInstance(ConfigRepositoryService::class);
                }
            );

            ServiceRegister::registerService(
                FrontendUserRepositoryInterface::class,
                function () {
                    return Helper::isCurrentVersion9OrHigher() ?
                        GeneralUtility::makeInstance(FrontendUserRepository::class) :
                        GeneralUtility::makeInstance(FrontendUserRepositoryLegacy::class);
                }
            );

            ServiceRegister::registerService(
                FrontendUserGroupRepositoryInterface::class,
                function () {
                    return Helper::isCurrentVersion9OrHigher() ?
                        GeneralUtility::makeInstance(FrontendUserGroupRepository::class) :
                        GeneralUtility::makeInstance(FrontendUserGroupRepositoryLegacy::class);
                }
            );

            ServiceRegister::registerService(
                PageRepositoryInterface::class,
                function () {
                    return Helper::isCurrentVersion9OrHigher() ?
                        GeneralUtility::makeInstance(PageRepository::class) :
                        GeneralUtility::makeInstance(PageRepositoryLegacy::class);
                }
            );

        } catch (\InvalidArgumentException $exception) {
            // Don't do nothing if service is already registered
        }
    }
}
