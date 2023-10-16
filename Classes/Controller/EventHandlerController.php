<?php

namespace WebanUg\Cleverreach\Controller;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class EventHandlerController extends ActionController
{
    const ALLOWED_EVENTS = [
        'receiver.subscribed',
        'receiver.unsubscribed'
    ];

    /** @var \CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService **/
    private $configService;

    /**
     * Handles webhook action.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function handleWebhooksAction()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $this->confirmHandler();
        }
        
        if ($method === 'POST') {
            $this->handleEvent();
        }

        Helper::diePlain('Incorrect HTTP method.', 'HTTP/1.1 400 Bad Request');
    }

    private function confirmHandler()
    {
        $secret = GeneralUtility::_GET('secret');
        if (empty($secret)) {
            Helper::diePlain('Secret is missing.', 'HTTP/1.1 400 Bad Request');
        }

        $token = $this->getConfigService()->getCrEventHandlerVerificationToken();

        Helper::diePlain("$token $secret");
    }

    /**
     * Handles webhook event.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function handleEvent()
    {
        $body = $this->getRequestBodyIfValid();

        /** @var \CleverReach\BusinessLogic\Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $crRecipient = $proxy->getRecipient($body['payload']['group_id'], $body['payload']['pool_id']);

        if (!$crRecipient) {
            Helper::diePlain('CleverReach recipient not valid.', 'HTTP/1.1 400 Bad Request');
        }

        switch ($body['event']) {
            case 'receiver.subscribed':
                $this->handleReceiverSubscribedEvent($crRecipient);
                break;
            case 'receiver.unsubscribed':
                $this->handleReceiverUnsubscribedEvent($crRecipient);
                break;
        }

        Helper::diePlain();
    }

    /**
     * Validates event handle payload and terminates request if not needed to proceed.
     *
     * @return array
     */
    private function getRequestBodyIfValid()
    {
        $callToken = $_SERVER['HTTP_X_CR_CALLTOKEN'];
        if ($callToken !== $this->getConfigService()->getCrEventHandlerCallToken()) {
            Helper::diePlain('Unauthorized', 'HTTP/1.1 401 Unauthorized');
        }

        $body = json_decode(file_get_contents('php://input'), true);

        $isEventTypeValid = isset($body['event']) && in_array($body['event'], self::ALLOWED_EVENTS, true);
        $isEventPayloadValid = isset($body['payload']['group_id'], $body['payload']['pool_id']);

        if (!($isEventPayloadValid && $isEventTypeValid)) {
            Helper::diePlain('Payload invalid.', 'HTTP/1.1 400 Bad Request');
        }

        if ($body['payload']['group_id'] !== strval($this->getConfigService()->getIntegrationId())) {
            Helper::diePlain();
        }

        return $body;
    }

    /**
     * Handles recipient subscribed event.
     *
     * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function handleReceiverSubscribedEvent(Recipient $recipient)
    {
        $this->processEvent($recipient, 1);
    }

    /**
     * Handles recipient unsubscribed event.
     *
     * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function handleReceiverUnsubscribedEvent(Recipient $recipient)
    {
        $this->processEvent($recipient, 0);
    }

    /**
     * Processes cleverreach event.
     *
     * @param \CleverReach\BusinessLogic\Entity\Recipient $recipient
     * @param int $status
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    private function processEvent(Recipient $recipient, $status)
    {
        /** @var FrontendUserRepositoryInterface $repository */
        $repository = ServiceRegister::getService(FrontendUserRepositoryInterface::class);
        $user = $repository->getUserByEmail($recipient->getEmail());
        if (empty($user) || (int)Helper::getValueIfNotEmpty('deleted', $user)) {
            return;
        }

        $repository->setCrNewsletterSubscriptionStatus((int) $user['uid'], $status);

        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);

        $queue->enqueue(
            $this->getConfigService()->getQueueName(),
            new RecipientSyncTask([$user['uid']])
        );
    }

    /**
     * Retrieves config service.
     *
     * @return \CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService
     */
    private function getConfigService()
    {
        if (!$this->configService) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
