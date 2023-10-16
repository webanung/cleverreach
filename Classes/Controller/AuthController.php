<?php

namespace WebanUg\Cleverreach\Controller;

use CleverReach\BusinessLogic\Entity\AuthInfo;
use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\BusinessLogic\Proxy\AuthProxy;
use CleverReach\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\Infrastructure\Exceptions\BadAuthInfoException;
use CleverReach\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use Webanug\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class AuthController
 * @package WebanUg\Cleverreach\Controller
 */
class AuthController extends ActionController
{
    /**
     * CleverReach oAuth callback method
     *
     * @throws InvalidConfigurationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    public function oauth2callbackAction()
    {
        $code = GeneralUtility::_GET('code');
        if (empty($code)) {
            Helper::dieJson(['status' => false, 'message' => 'Wrong parameters. Code not set.']);
        }

        $refresh = GeneralUtility::_GET('refresh');

        if (!empty($refresh)) {
            $authInfo = $this->getAuthInfo($code, true);
            $this->refreshTokens($authInfo);
        } else {
            $authInfo = $this->getAuthInfo($code, false);

            try {
                $this->queueRefreshUserTask($authInfo);
            } catch (QueueStorageUnavailableException $e) {
                $this->errorResponse($e);
            }
        }

        die('<script>window.close()</script>');
    }

    /**
     * @param string $code
     * @param bool $refreshTokens
     *
     * @return AuthInfo
     *
     * @throws HttpCommunicationException
     */
    private function getAuthInfo($code, $refreshTokens = false)
    {
       /** @var AuthProxy $proxy */
        $proxy = ServiceRegister::getService(AuthProxy::CLASS_NAME);
        $callbackUrl = Helper::getCallbackUri($refreshTokens);
        $result = null;

        try {
            $result = $proxy->getAuthInfo($code, $callbackUrl);
        } catch (BadAuthInfoException $e) {
            Helper::dieJson(['status' => false, 'message' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * @param AuthInfo $authInfo
     *
     * @throws QueueStorageUnavailableException
     */
    private function queueRefreshUserTask($authInfo)
    {
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);
        /** @var \WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);

        $queue->enqueue($configService->getQueueName(), new RefreshUserInfoTask($authInfo));
    }

    /**
     * @param AuthInfo $authInfo
     *
     * @throws InvalidConfigurationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    private function refreshTokens($authInfo)
    {
        /** @var ConfigurationService $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var \CleverReach\BusinessLogic\Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        $userInfo = $proxy->getUserInfo($authInfo->getAccessToken());
        $localInfo = $config->getUserInfo();

        if (isset($userInfo['id']) && $userInfo['id'] === $localInfo['id']) {
            $config->setAccessToken($authInfo->getAccessToken());
            $config->setRefreshToken($authInfo->getRefreshToken());
            $config->setAccessTokenExpirationTime($authInfo->getAccessTokenDuration());
        }
    }

    /**
     * @param \Exception $e
     */
    private function errorResponse(\Exception $e)
    {
        Helper::dieJson(['message' => $e->getMessage(), 'status' => $e->getCode()]);
    }
}
