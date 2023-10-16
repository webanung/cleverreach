<?php

namespace WebanUg\Cleverreach\Utility;

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
use WebanUg\Cleverreach\Domain\Repository\FrontendUserGroupRepository;
use WebanUg\Cleverreach\Domain\Repository\FrontendUserRepository;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use WebanUg\Cleverreach\Domain\Repository\Legacy\FrontendUserGroupRepository as FrontendUserGroupRepositoryLegacy;
use WebanUg\Cleverreach\Domain\Repository\Legacy\FrontendUserRepository as FrontendUserRepositoryLegacy;
use WebanUg\Cleverreach\Domain\Repository\Legacy\PageRepository as PageRepositoryLegacy;
use WebanUg\Cleverreach\Domain\Repository\PageRepository;
use WebanUg\Cleverreach\IntegrationServices\Business\AttributesService;
use WebanUg\Cleverreach\IntegrationServices\Business\RecipientsService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\AsyncProcessStarterService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigRepositoryService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\HttpClientService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\LoggerService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\TaskQueueStorageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Initializer
 * @package WebanUg\Cleverreach\Utility
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
