<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Proxy\FormProxy;
use CleverReach\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;

/**
 * Class FormSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class FormSyncTask extends BaseSyncTask
{
    /**
     * @var FormProxy
     */
    private $formProxy;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->reportProgress(20);

        try {
            $integrationId = $this->getConfigService()->getIntegrationId();
            $formName = $this->getConfigService()->getIntegrationFormName();
            if (!$this->formExists($integrationId, $formName)) {
                $this->reportProgress(50);
                $this->getFormProxy()->createForm($integrationId, $formName);
            }
        } catch (\Exception $e) {
            Logger::logError('Failed to create form. Error: ' . $e->getMessage());
        }

        $this->reportProgress(100);
    }

    /**
     * Checks if form exists for given integration
     *
     * @param int $integrationId CleverReach group name
     * @param string $formName CleverReach form name
     *
     * @return bool
     *
     * @throws InvalidConfigurationException
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws RefreshTokenExpiredException
     */
    private function formExists($integrationId, $formName)
    {
        $existingForms = $this->getFormProxy()->getFormList();
        $this->reportAlive();
        foreach ($existingForms as $form) {
            if ($form['name'] === $formName && (int)$form['customer_tables_id'] === $integrationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return FormProxy
     */
    private function getFormProxy()
    {
        if ($this->formProxy === null) {
            $this->formProxy = ServiceRegister::getService(FormProxy::CLASS_NAME);
        }

        return $this->formProxy;
    }
}
