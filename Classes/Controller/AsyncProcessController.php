<?php
namespace WebunUg\Cleverreach\Controller;

use CleverReach\Infrastructure\Logger\Logger;
use WebanUg\Cleverreach\Domain\Model\Process;
use WebanUg\Cleverreach\Domain\Repository\ProcessRepository;
use WebanUg\Cleverreach\Domain\Repository\Legacy\ProcessRepository as ProcessLegacyRepository;
use WebanUg\Cleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class AsyncProcessController extends ActionController
{
    /**
     * Async process endpoint action
     */
    public function runAction()
    {
        try {
            $guid = GeneralUtility::_GET('guid');
            /** @var ProcessRepository $processRepository */
            $processRepository = $this->getProcessRepository();
            /** @var Process $process */
            $process = $processRepository->find($guid);
            if ($process === null) {
                Logger::logError('Failed to start runner asynchronously. Process guid not found.', 'Integration');

                Helper::diePlain();
            }

            $runner = unserialize($process->getRunner());
            if (empty($runner)) {
                Logger::logError(
                    'Failed to start runner asynchronously. Runner deserialization failed.',
                    'Integration'
                );

                Helper::diePlain();
            }

            $runner->run();
            $processRepository->delete($guid);
        } catch (\Exception $e) {
            Logger::logError($e->getMessage(), 'Integration');
        }

        Helper::diePlain();
    }

    /**
     * Returns an instance of process repository.
     *
     * @return \CR\OfficialCleverreach\Domain\Repository\Interfaces\ProcessRepositoryInterface
     */
    private function getProcessRepository()
    {
        if (Helper::isCurrentVersion9OrHigher()) {
            return GeneralUtility::makeInstance(ObjectManager::class)->get(ProcessRepository::class);
        }

        return GeneralUtility::makeInstance(ObjectManager::class)->get(ProcessLegacyRepository::class);
    }
}
