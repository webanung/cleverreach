<?php

namespace CR\OfficialCleverreach\Controller;

require_once __DIR__ . '/../autoload.php';

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class SupportController extends ActionController
{
    /**
     * @var ConfigurationService $configService
     */
    private $configService;

    /**
     * @return string
     */
    public function supportAction()
    {
        header('Content-Type: application/json');

        $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        if ($this->request->getMethod() === 'POST') {
            $result = $this->updateConfigParameters();
        } else {
            $result = $this->getConfigParameters();
        }

        return json_encode($result);
    }

    /**
     * Get endpoint
     *
     * @return array
     */
    private function getConfigParameters()
    {
        return [
            'integrationId' => $this->configService->getIntegrationId(),
            'integrationName' => $this->configService->getIntegrationName(),
            'minLogLevel' => $this->configService->getMinLogLevel(),
            'isProductSearchEnabled' => $this->configService->isProductSearchEnabled(),
            'productSearchParameters' => $this->configService->getProductSearchParameters(),
            'recipientsSynchronizationBatchSize' => $this->configService->getRecipientsSynchronizationBatchSize(),
            'isDefaultLoggerEnabled' => $this->configService->isDefaultLoggerEnabled(),
            'maxStartedTasksLimit' => $this->configService->getMaxStartedTasksLimit(),
            'maxTaskExecutionRetries' => $this->configService->getMaxTaskExecutionRetries(),
            'maxTaskInactivityPeriod' => $this->configService->getMaxTaskInactivityPeriod(),
            'taskRunnerMaxAliveTime' => $this->configService->getTaskRunnerMaxAliveTime(),
            'taskRunnerStatus' => $this->configService->getTaskRunnerStatus(),
            'taskRunnerWakeupDelay' => $this->configService->getTaskRunnerWakeupDelay(),
            'queueName' => $this->configService->getQueueName(),
            'currentSystemVersion' => VersionNumberUtility::getCurrentTypo3Version(),
            'eventHandlerUrl' => $this->configService->getCrEventHandlerURL(),
        ];
    }

    /**
     * Post endpoint
     *
     * @return array
     */
    private function updateConfigParameters()
    {
        $payload = json_decode(file_get_contents('php://input'), true);

        if (array_key_exists('minLogLevel', $payload)) {
            $this->configService->saveMinLogLevel($payload['minLogLevel']);
        }

        if (array_key_exists('defaultLoggerStatus', $payload)) {
            $this->configService->setDefaultLoggerEnabled($payload['defaultLoggerStatus']);
        }

        if (array_key_exists('maxStartedTasksLimit', $payload)) {
            $this->configService->setMaxStartedTaskLimit($payload['maxStartedTasksLimit']);
        }

        if (array_key_exists('taskRunnerWakeUpDelay', $payload)) {
            $this->configService->setTaskRunnerWakeUpDelay($payload['taskRunnerWakeUpDelay']);
        }

        if (array_key_exists('taskRunnerMaxAliveTime', $payload)) {
            $this->configService->setTaskRunnerMaxAliveTime($payload['taskRunnerMaxAliveTime']);
        }

        if (array_key_exists('maxTaskExecutionRetries', $payload)) {
            $this->configService->setMaxTaskExecutionRetries($payload['maxTaskExecutionRetries']);
        }

        if (array_key_exists('recipientsSynchronizationBatchSize', $payload)) {
            $this->configService->setRecipientsSynchronizationBatchSize($payload['recipientsSynchronizationBatchSize']);
        }

        if (array_key_exists('maxTaskInactivityPeriod', $payload)) {
            $this->configService->setMaxTaskInactivityPeriod($payload['maxTaskInactivityPeriod']);
        }

        if (array_key_exists('resetAccessToken', $payload)) {
            $this->configService->setAccessToken(null);
        }

        return ['message' => 'Successfully updated config values!'];
    }
}
