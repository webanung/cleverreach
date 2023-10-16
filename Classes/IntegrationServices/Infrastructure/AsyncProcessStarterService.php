<?php

namespace CR\OfficialCleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\Infrastructure\Utility\GuidProvider;
use CR\OfficialCleverreach\Domain\Model\Process;
use CR\OfficialCleverreach\Domain\Repository\ProcessRepository;
use CR\OfficialCleverreach\Domain\Repository\Legacy\ProcessRepository as ProcessLegacyRepository;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class AsyncProcessStarterService implements AsyncProcessStarter
{
    /**
     * @var HttpClientService $httpClientService
     */
    private $httpClientService;
    /**
     * @var ProcessRepository $processRepository
     */
    private $processRepository;

    /**
     * AsyncProcessStarterService constructor.
     */
    public function __construct()
    {
        if (Helper::isCurrentVersion9OrHigher()) {
            $processRepositoryClass = ProcessRepository::class;
        } else {
            $processRepositoryClass = ProcessLegacyRepository::class;
        }

        $this->processRepository = GeneralUtility::makeInstance(ObjectManager::class)->get($processRepositoryClass);
    }

    /**
     * @param Runnable $runner
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function start(Runnable $runner)
    {
        $guidProvider = new GuidProvider();
        $guid = trim($guidProvider->generateGuid());

        $this->saveGuidAndRunner($guid, $runner);
        $this->startRunnerAsynchronously($guid);
    }

    /**
     * @param Runnable $runner
     * @param string $guid
     *
     * @throws ProcessStarterSaveException
     */
    protected function saveGuidAndRunner($guid, $runner)
    {
        try {
            $process = new Process($guid, serialize($runner));

            $this->processRepository->save($process);
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), 'Integration');
            throw new ProcessStarterSaveException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $guid
     *
     * @throws HttpRequestException
     */
    protected function startRunnerAsynchronously($guid)
    {
        try {
            $this->getHttpClient()->requestAsync('GET', $this->formatAsyncProcessStartUrl($guid));
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), 'Integration');
            throw new HttpRequestException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $guid
     *
     * @return string
     */
    protected function formatAsyncProcessStartUrl($guid)
    {
        $baseEidUrl = GeneralUtility::locationHeaderUrl('/index.php?eID=cleverreach_frontend&guid=' . $guid);

        return $baseEidUrl . (Helper::isCurrentVersion9OrHigher()
            ? Helper::buildQueryStringForVersion9OrLater('AsyncProcess', 'run')
            : Helper::buildQueryStringForVersion8OrPrevious(Helper::CLEVERREACH_ASYNC));
    }

    /**
     * @return \CR\OfficialCleverreach\IntegrationServices\Infrastructure\HttpClientService
     */
    private function getHttpClient()
    {
        if ($this->httpClientService === null) {
            $this->httpClientService = ServiceRegister::getService(HttpClient::CLASS_NAME);
        }

        return $this->httpClientService;
    }
}
