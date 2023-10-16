<?php

namespace WebanUg\Cleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

class LoggerService implements ShopLoggerAdapter
{
    /**
     * @param \CleverReach\Infrastructure\Logger\LogData $data
     */
    public function logMessage($data)
    {
        /** @var ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $minLogLevel = $configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();

        if ($logLevel > $minLogLevel) {
            return;
        }

        $this->setLogFile();
        /**
         * @var \TYPO3\CMS\Core\Log\Logger $logger
         */
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        switch ($logLevel) {
            case Logger::ERROR:
                $logger->error($data->getMessage());
                break;
            case Logger::WARNING:
                $logger->warning($data->getMessage());
                break;
            case Logger::INFO:
                $logger->info($data->getMessage());
                break;
            case Logger::DEBUG:
                $logger->debug($data->getMessage());
                break;
            default:
                $logger->notice($data->getMessage());
                break;
        }
    }

    private function setLogFile()
    {
        $time = new \DateTime();
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['CR']['OfficialCleverreach']['writerConfiguration'] = [
            // configuration for ERROR level log entries
            LogLevel::DEBUG => [
                // add a FileWriter
                FileWriter::class => [
                    // configuration for the writer
                    'logFile' => 'typo3temp/logs/cleverreach/' . $time->format('Y-m-d') . '.log',
                ]
            ]
        ];
    }
}