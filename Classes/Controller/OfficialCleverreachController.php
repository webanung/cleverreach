<?php

namespace CR\OfficialCleverreach\Controller;

require_once __DIR__ . '/../autoload.php';

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Proxy\AuthProxy;
use CleverReach\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use CR\OfficialCleverreach\IntegrationServices\Sync\InitialSyncTask;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class OfficialCleverreachController
 * @package CR\OfficialCleverreach\Controller
 */
class OfficialCleverreachController extends ActionController
{
    const CLEVERREACH_BUILD_EMAIL_URL = '/admin/mailing_create_new.php';

    /**
     * @var ConfigurationService $configService
     */
    private $configService;
    /**
     * @var Queue $queue
     */
    private $queue;
    /**
     * @var TaskRunnerWakeup
     */
    private $taskRunnerWakeupService;

    /**
     * Renders welcome page
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function welcomeAction()
    {
        $this->redirectIfNecessary($this->request->getControllerActionName());
        $this->getConfigService()->saveUserLanguage(Helper::getLanguage());

        $viewParams = [
            // since AuthController is frontend controller, uriBuilder cannot be used for getting callback url
            'authUrl' => $this->getRedirectUrl(),
            'checkStatusUrl' => $this->uriBuilder->uriFor('userInfo', [], 'CheckStatus'),
            'hello' => Helper::getTranslation('tx_officialcleverreach_hello', 'hello'),
        ];

        $this->view->assignMultiple($viewParams);
        $this->getConfigService()->saveSiteName();
    }

    /**
     * Renders initial sync page
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initialSyncTaskAction()
    {
        $this->redirectIfNecessary($this->request->getControllerActionName());
        $viewParams = [
            'checkStatusUrl' => $this->uriBuilder->uriFor('initialSync', [], 'CheckStatus'),
            'recipientSyncTaskTitle' => Helper::getTranslation(
                'tx_officialcleverreach_initial_sync_import_subscribers',
                'Import recipients from TYPO3 CMS to CleverReach®'
            ),
        ];
        $this->view->assignMultiple($viewParams);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function tokenExpiredAction()
    {
        $userInfo = $this->getConfigService()->getUserInfo();
        $this->redirectIfNecessary($this->request->getControllerActionName());
        // since AuthController is frontend controller, uriBuilder cannot be used for getting callback url
        $this->view->assign('authUrl', $this->getRedirectUrl(true));
        $this->view->assign('checkStatusUrl', $this->uriBuilder->uriFor('userInfo', [], 'CheckStatus'));
        $this->view->assign('hello', Helper::getTranslation('tx_officialcleverreach_hello', 'hello'));
        $this->view->assign('integrationName', $this->getConfigService()->getIntegrationName());
        $this->view->assign('clientId', $userInfo['id']);
        $this->getConfigService()->saveSiteName();
    }

    /**
     * Renders dashboard page
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function dashboardAction()
    {
        $this->redirectIfNecessary($this->request->getControllerActionName());
        $this->getTaskRunnerWakeupService()->wakeup();
        $userInfo = $this->getConfigService()->getUserInfo();
        $failureParameters = $this->getInitialSyncFailureParameters();
        $viewParams = [
            'buildFirstEmailUrl' => $this->uriBuilder->uriFor('buildFirstEmail', [], 'OfficialCleverreach'),
            'retrySyncUrl' => $this->uriBuilder->uriFor('retrySync', [], 'OfficialCleverreach'),
            'recipientId' => $userInfo['id'],
            'buildEmailUrl' => SingleSignOnProvider::getUrl(self::CLEVERREACH_BUILD_EMAIL_URL),
            'isFirstEmailBuilt' => $this->getConfigService()->isFirstEmailBuilt(),
            'isTaskFailed' => $failureParameters['isFailed'],
            'taskFailureMessage' => $failureParameters['description'],
        ];

        if (!$failureParameters['isFailed']) {
            $viewParams['report'] = $this->getInitialSyncReport();
        }

        $this->view->assignMultiple($viewParams);
    }

    /**
     * @return string
     */
    public function buildFirstEmailAction()
    {
        $this->getConfigService()->setIsFirstEmailBuilt(1);

        return json_encode(['status' => 'success']);
    }

    /**
     * @return string
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function retrySyncAction()
    {
        $this->getQueueService()->enqueue($this->getConfigService()->getQueueName(), new InitialSyncTask());

        return json_encode(['status' => 'success']);
    }

    /**
     * @param string $currentAction
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    private function redirectIfNecessary($currentAction)
    {
        $redirectAction = $this->getRedirectAction();

        if ($redirectAction !== $currentAction) {
            $this->redirect($redirectAction, 'OfficialCleverreach');
        }
    }

    /**
     * @param bool $refreshTokens
     *
     * @return string
     */
    private function getRedirectUrl($refreshTokens = false)
    {
        $callbackUrl = Helper::getCallbackUri($refreshTokens);

        /** @var AuthProxy $proxy */
        $proxy = ServiceRegister::getService(AuthProxy::CLASS_NAME);

        return $proxy->getAuthUrl($callbackUrl, Helper::getUserData(), ['lang' => Helper::getLanguage()]);
    }

    /**
     * @return string
     */
    private function getRedirectAction()
    {
        if (!$this->isAuthTokenValid()) {
            return 'welcome';
        }

        if ($this->isInitialSyncInProgress()) {
            return 'initialSyncTask';
        }

        if (!$this->checkIfRefreshTokenExists()) {
            return 'tokenExpired';
        }

        return 'dashboard';
    }

    /**
     * @return bool
     */
    private function isInitialSyncInProgress()
    {
        /** @var QueueItem $queueItem */
        $queueItem = $this->getQueueService()->findLatestByType(InitialSyncTask::getClassName());
        if ($queueItem === null) {
            try {
                $this->getQueueService()->enqueue($this->getConfigService()->getQueueName(), new InitialSyncTask());
            } catch (QueueStorageUnavailableException $e) {
                // If task enqueue fails do nothing but report that initial sync is in progress
            }

            return true;
        }

        return $queueItem->getStatus() !== QueueItem::COMPLETED
            && $queueItem->getStatus() !== QueueItem::FAILED;
    }

    /**
     * @return array
     */
    private function getInitialSyncFailureParameters()
    {
        $params = ['isFailed' => false, 'description' => ''];
        /** @var QueueItem $queueItem */
        $queueItem = $this->getQueueService()->findLatestByType(InitialSyncTask::getClassName());
        if ($queueItem && $queueItem->getStatus() === QueueItem::FAILED) {
            $params = [
                'isFailed' => true,
                'description' => $queueItem->getFailureDescription(),
            ];
        }

        return $params;
    }

    /**
     * Generates initial sync report
     *
     * @return array
     */
    private function getInitialSyncReport()
    {
        $configService = $this->getConfigService();
        $result = [
            'isReportEnabled' => !$configService->isImportStatisticsDisplayed(),
            'reportDynamicClass' => ''
        ];

        if ($result['isReportEnabled']) {
            $language = Helper::getLanguage();
            $result['reportDynamicClass'] = 'cr-has-import';
            $result['recipients'] = number_format(
                $configService->getNumberOfSyncedRecipients(),
                0,
                $language === 'en' ? '.' : ',',
                $language === 'en' ? ',' : '.'
            );
            $result['name'] = $configService->getIntegrationListName();
            $result['locale'] = Helper::getLanguage();
            $result['tags'] = $this->getFormattedTags();
            $configService->setImportStatisticsDisplayed(true);
        }

        return $result;
    }

    /**
     * Generates formatted tags.
     *
     * @return array
     */
    private function getFormattedTags()
    {
        $result = [];
        /** @var Recipients $recipientsService */
        $recipientsService = ServiceRegister::getService(Recipients::CLASS_NAME);
        $tags = $recipientsService->getAllTags()->getTags();
        $numberOfTags = \count($tags);
        if ($numberOfTags === 0) {
            return $result;
        }

        $trimmedTags = array_slice($tags, 0, 3);
        foreach ($trimmedTags as $index => $tag) {
            $result[] = '<div class="value" title="' . $tag->getTitle() . '">'
                            . $this->getTrimmedTagName($index + 1 . ') ' . $tag->getTitle())
                        . '</div>';
        }

        if ($numberOfTags > 3) {
            $result[] = '<div class="value">...</div>';
        }

        return $result;
    }

    /**
     * Generates trimmed tag name.
     *
     * @param string $tag
     * @param int $maxChars
     * @param string $filler
     *
     * @return string
     */
    private function getTrimmedTagName($tag, $maxChars = 24, $filler = '...')
    {
        $length = \strlen($tag);
        $filterLength = \strlen($filler);

        return $length > $maxChars ? substr_replace(
            $tag,
            $filler,
            $maxChars - $filterLength,
            $length - $maxChars
        ) : $tag;
    }

    /**
     * @return bool
     */
    private function isAuthTokenValid()
    {
        $authToken = $this->getConfigService()->getAccessToken();

        return $authToken !== null;
    }

    /**
     * @return bool
     */
    private function checkIfRefreshTokenExists()
    {
        return $this->getConfigService()->getRefreshToken() !== null;
    }

    /**
     * @return Queue
     */
    private function getQueueService()
    {
        if ($this->queue === null) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }

    /**
     * @return TaskRunnerWakeup
     */
    private function getTaskRunnerWakeupService()
    {
        if ($this->taskRunnerWakeupService === null) {
            $this->taskRunnerWakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
        }

        return $this->taskRunnerWakeupService;
    }

    /**
     * @return ConfigurationService
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
