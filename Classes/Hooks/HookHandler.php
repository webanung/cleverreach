<?php

namespace WebanUg\Cleverreach\Hooks;

require_once __DIR__ . '/../autoload.php';

use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Sync\FilterSyncTask;
use CleverReach\BusinessLogic\Sync\RecipientDeactivateSyncTask;
use CleverReach\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\BusinessLogic\Sync\RegisterEventHandlerTask;
use CleverReach\BusinessLogic\Sync\ExchangeAccessTokenTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\Task;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use WebanUg\Cleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use WebanUg\Cleverreach\IntegrationServices\Business\RecipientsService;
use WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use WebanUg\Cleverreach\Utility\Helper;
use In2code\Femanager\Domain\Model\User;
use In2code\Femanager\Event\AfterUserUpdateEvent;
use In2code\Femanager\Event\BeforeUpdateUserEvent;
use In2code\Femanager\Event\DeleteUserEvent;
use In2code\Femanager\Event\FinalCreateEvent;
use In2code\Femanager\Event\FinalUpdateEvent;
use \TYPO3\CMS\Core\DataHandling\DataHandler;

class HookHandler
{
    const GROUPS_TABLE = 'fe_groups';
    const USERS_TABLE = 'fe_users';
    const PAGES_TABLE = 'pages';
    /**
     * @var ConfigurationService $configService
     */
    private $configService;
    /**
     * @var Queue $queueService
     */
    private $queueService;
    /**
     * @var FrontendUserGroupRepositoryInterface $userGroupsRepository
     */
    private $userGroupsRepository;
    /**
     * @var FrontendUserRepositoryInterface $userRepository
     */
    private $userRepository;
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;
    /**
     * @var array $tagsForDelete
     */
    private static $tagsForDelete = [];
    /**
     * @var string
     */
    private static $emailForDelete;
    /**
     * @var int
     */
    private static $parentPageId;

    /**
     * @param FinalCreateEvent $event
     */
    public function femanagerUserCreated(FinalCreateEvent $event)
    {
        if ($event->getAction() !== 'new') {
            return;
        }

        if ($user = $event->getUser()) {
            $idsForAvoid = [];
            if (filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $this->enqueueTask(new FilterSyncTask());
                $this->enqueueTask(new RecipientSyncTask([$user->getUid()]));
                $idsForAvoid[] = $user->getUid();
            }

            $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
        }
    }

    /**
     * @param BeforeUpdateUserEvent $event
     */
    public function femanagerUserUpdatedBefore(BeforeUpdateUserEvent $event)
    {
        if ($user = $event->getUser()) {
            self::$emailForDelete = $this->getUserRepository()->getUserEmailById($user->getUid());
        }
    }

    /**
     * @param AfterUserUpdateEvent $event
     */
    public function femanagerUserUpdatedAfter(AfterUserUpdateEvent $event)
    {
        if ($user = $event->getUser()) {
            $this->femanagerUserUpdatedHandler($user);
        }
    }

    /**
     * @param FinalUpdateEvent $event
     */
    public function femanagerFinalUpdate(FinalUpdateEvent $event)
    {
        if ($user = $event->getUser()) {
            $this->femanagerUserUpdatedHandler($user);
        }
    }

    /**
     * @param DeleteUserEvent $event
     */
    public function femanagerUserDeleteBefore(DeleteUserEvent $event)
    {
        if ($user = $event->getUser()) {
            $this->enqueueRecipientDeactivateTask($user->getEmail());
            $this->resyncRecipientsIfSiteNameChanged();
            $this->enqueueTask(new FilterSyncTask());
        }
    }

    /**
     * Handles user delete from frontend (via sr_feuser_register)
     *
     * @param array $origArray
     * @param \SJBR\SrFeuserRegister\Domain\Data $processor
     */
    public function registrationProcess_beforeSaveDelete(array $origArray, $processor)
    {
        if (array_key_exists('email', $origArray)) {
            $this->enqueueRecipientDeactivateTask($origArray['email']);
            $this->enqueueTask(new FilterSyncTask());
        }

        $this->resyncRecipientsIfSiteNameChanged();
    }

    /**
     * Handles user create from frontend (via sr_feuser_register)
     *
     * @param string $table
     * @param array $dataArray
     * @param array $origArray
     * @param string $token
     * @param array $newRow
     * @param $cmd
     * @param $cmdKey
     * @param string|int $pid
     * @param $extraList
     * @param \SJBR\SrFeuserRegister\Domain\Data $processor
     */
    public function registrationProcess_afterSaveCreate(
        $table,
        array $dataArray,
        array $origArray,
        $token,
        array $newRow,
        $cmd,
        $cmdKey,
        $pid,
        $extraList,
        $processor
    ) {
        if ($table !== self::USERS_TABLE) {
            return;
        }

        $idsForAvoid = [];
        if (filter_var(Helper::getValueIfNotEmpty('email', $dataArray), FILTER_VALIDATE_EMAIL)) {
            $this->enqueueTask(new FilterSyncTask());
            $this->enqueueTask(new RecipientSyncTask([$dataArray['uid']]));
            $idsForAvoid[] = $dataArray['uid'];
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
    }

    /**
     * Handles user update from frontend (via sr_feuser_register)
     *
     * @param string $table
     * @param array $dataArray
     * @param array $origArray
     * @param string $token
     * @param array $newRow
     * @param $cmd
     * @param $cmdKey
     * @param $pid
     * @param $extraList
     * @param \SJBR\SrFeuserRegister\Domain\Data $processor
     */
    public function registrationProcess_afterSaveEdit(
        $table,
        array $dataArray,
        array $origArray,
        $token,
        array $newRow,
        $cmd,
        $cmdKey,
        $pid,
        $extraList,
        $processor
    ) {
        if ($table !== self::USERS_TABLE) {
            return;
        }

        $idsForAvoid = [];
        if ($this->isEmailChanged($newRow, $origArray)) {
            $this->enqueueRecipientDeactivateTask($origArray['email']);
        }

        if (filter_var(Helper::getValueIfNotEmpty('email', $newRow), FILTER_VALIDATE_EMAIL)) {
            $this->enqueueTask(new RecipientSyncTask([$dataArray['uid']]));
            $idsForAvoid[] = $dataArray['uid'];
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
    }

    /**
     * Trigger after delete from database
     *
     * @param string $command
     * @param string $table
     * @param string|int $id
     * @param string $value
     * @param DataHandler $processor
     * @param $pasteUpdate
     * @param $pasteDatamap
     */
    public function processCmdmap_postProcess(
        $command,
        $table,
        $id,
        $value,
        DataHandler $processor,
        $pasteUpdate,
        $pasteDatamap
    ) {
        if ($command !== 'delete') {
            return;
        }

        if ($table === self::GROUPS_TABLE) {
            $this->handleUserGroupUpdatedAfter($id);
        } elseif ($table === self::USERS_TABLE) {
            // typo3 uses soft delete so user with given id is marked as deleted
            // and it will be synced as inactive
            $this->enqueueTask(new RecipientSyncTask([$id]));
            $this->enqueueTask(new FilterSyncTask());
            $this->resyncRecipientsIfSiteNameChanged([$id]);
        } elseif ($table === self::PAGES_TABLE) {
            $this->handleFolderDeleteAfter($id);
        }

        self::$tagsForDelete = [];
    }

    /**
     * Trigger after save to database
     *
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $processor
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $processor
    ) {
        if ($table === self::GROUPS_TABLE) {
            if ($status === 'new') {
                $this->enqueueTask(new FilterSyncTask());
                $this->resyncRecipientsIfSiteNameChanged();
            } else {
                $this->handleUserGroupUpdatedAfter($id);
            }
        } elseif ($table === self::USERS_TABLE) {
            if ($status === 'new') {
                $this->handleNewUserCreated($processor->substNEWwithIDs[$id], $fieldArray);
            } else {
                $this->handleUserUpdatedAfter($id);
            }
        } elseif ($table === self::PAGES_TABLE) {
            if ($status === 'update') {
                $this->handleFolderUpdateAfter($id);
            }
        }
    }

    /**
     * Trigger before delete action
     *
     * @param string $table
     * @param string|int $id
     * @param $recordWasDeleted
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $processor
     */
    public function processCmdmap_deleteAction(
        $table,
        $id,
        array $recordToDelete,
        &$recordWasDeleted,
        DataHandler $processor
    ) {
        if ($table === self::GROUPS_TABLE) {
            self::$tagsForDelete = $this->getUserGroupsRepository()->getGroupTitleByUid($id);
        }
    }

    /**
     * Trigger before saving to database
     *
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $processor
     */
    public function processDatamap_postProcessFieldArray(
        $status,
        $table,
        $id,
        array $fieldArray,
        DataHandler $processor
    ) {
        if ($status !== 'update') {
            return;
        }

        if ($table === self::GROUPS_TABLE) {
            $this->handleUserGroupUpdatedBefore($id);
        } elseif ($table === self::USERS_TABLE) {
            $this->handleUserUpdatedBefore($id, $fieldArray);
        } elseif ($table === self::PAGES_TABLE) {
            $this->handleFolderUpdateBefore($id);
        }
    }

    /**
     * @param string $extname
     */
    public function uninstall($extname)
    {
        if ($extname === 'official_cleverreach') {
            $this->getDBConnection()->exec_TRUNCATEquery('tx_officialcleverreach_domain_model_queue');
            $this->getDBConnection()->exec_TRUNCATEquery('tx_officialcleverreach_domain_model_process');
            $this->getDBConnection()->exec_TRUNCATEquery('tx_officialcleverreach_domain_model_configuration');
        }
    }

    /**
     * @param string $extname
     */
    public function install($extname = null)
    {
        if ($extname !== 'official_cleverreach') {
            return;
        }

        $db = $this->getDBConnection();
        $taskRunnerStatus = $db->exec_SELECTgetRows(
            '*',
            'tx_officialcleverreach_domain_model_configuration',
            'cr_key=' . $db->fullQuoteStr('CLEVERREACH_TASK_RUNNER_STATUS',
                'tx_officialcleverreach_domain_model_configuration')
        );

        if (empty($taskRunnerStatus)) {
            $values = [
                'cr_key' => 'CLEVERREACH_TASK_RUNNER_STATUS',
                'cr_value' => json_encode(['guid' => '', 'timestamp' => null]),
            ];

            $db->exec_INSERTquery('tx_officialcleverreach_domain_model_configuration', $values);
        }

        $this->update110();
    }

    /**
     * @param User $user
     * @return void
     */
    private function femanagerUserUpdatedHandler(User $user)
    {
        $idsForAvoid = [];
        if (self::$emailForDelete !== $user->getEmail()) {
            $this->enqueueRecipientDeactivateTask(self::$emailForDelete);
        }

        if (filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $this->enqueueTask(new RecipientSyncTask([$user->getUid()]));
            $idsForAvoid[] = $user->getUid();
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
        self::$emailForDelete = null;
    }

    /**
     * Runs update script for version 1.1.0
     */
    private function update110()
    {
        $db = $this->getDBConnection();
        $task = $db->exec_SELECTgetSingleRow(
            '*',
            'tx_officialcleverreach_domain_model_queue',
            "status='completed' and type='RegisterEventHandlerTask'"
        );

        $token = $db->exec_SELECTgetSingleRow(
            '*',
            'tx_officialcleverreach_domain_model_configuration',
            'cr_key=' .
            $db->fullQuoteStr('CLEVERREACH_ACCESS_TOKEN', 'tx_officialcleverreach_domain_model_configuration') .
            ' and ' .
            'cr_value is not null'
        );

        if (empty($task) and !empty($token)) {
            /** @var Queue $queue */
            $queue = ServiceRegister::getService(Queue::CLASS_NAME);
            /** @var Configuration $service */
            $service = ServiceRegister::getService(Configuration::CLASS_NAME);
            try {
                $queue->enqueue(
                    $service->getQueueName(),
                    new RegisterEventHandlerTask()
                );
                $queue->enqueue(
                    $service->getQueueName(),
                    new ExchangeAccessTokenTask()
                );
            } catch (QueueStorageUnavailableException $e) {
                Logger::logError('Could not enqueue RegisterEventHandlerTask', 'Integration');
            }
        }
    }

    /**
     * Handles user create action
     *
     * @param int $id
     */
    private function handleNewUserCreated($id, $fieldArray)
    {
        $idsForAvoid = [];
        if (filter_var(Helper::getValueIfNotEmpty('email', $fieldArray), FILTER_VALIDATE_EMAIL)) {

            $this->enqueueTask(new FilterSyncTask());
            $this->enqueueTask(new RecipientSyncTask([$id]));
            $idsForAvoid[] = $id;
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
    }

    /**
     * If email is changed, save old one (this is necessary for deactivated email on CleverReach)
     *
     * @param int $id
     * @param array $dataMap
     */
    private function handleUserUpdatedBefore($id, $dataMap)
    {
        $user = $this->getUserRepository()->getUserById($id);
        if ($this->isEmailChanged($dataMap, $user)) {
            self::$emailForDelete = $user['email'];
        }
    }

    /**
     * Updates recipient
     *
     * @param int $id
     */
    private function handleUserUpdatedAfter($id)
    {
        if (!empty(self::$emailForDelete)) {
            $this->enqueueRecipientDeactivateTask(self::$emailForDelete);
        }

        $user = $this->getUserRepository()->getUserById($id);
        $idsForAvoid = [];
        if (filter_var(Helper::getValueIfNotEmpty('email', $user), FILTER_VALIDATE_EMAIL)) {
            $this->enqueueTask(new RecipientSyncTask([$id]));
            $idsForAvoid[] = $id;
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForAvoid);
        self::$emailForDelete = null;
    }

    /**
     * Saves old group name (this is necessary for deleting tag from recipient on CleverReach)
     *
     * @param int $id
     */
    private function handleUserGroupUpdatedBefore($id)
    {
        self::$tagsForDelete = $this->getUserGroupsRepository()->getGroupTitleByUid($id);
    }

    /**
     * @param int $id
     */
    private function handleFolderUpdateBefore($id)
    {
        $pageInfo = $this->getPageRepository()->fetchPageInfoById($id);
        self::$parentPageId = (int)$pageInfo['pid'];
        self::$tagsForDelete[] = Helper::getValueIfNotEmpty('title', $pageInfo);
    }

    /**
     * @param int $id
     */
    private function handleFolderUpdateAfter($id)
    {
        $idsForSync = $this->getUserIdsByFolderId($id);
        if (!empty($idsForSync)) {
            $this->enqueueTask(new FilterSyncTask());
            $this->enqueueTask(
                new RecipientSyncTask(
                    $idsForSync,
                    $this->formatTags(self::$tagsForDelete, RecipientsService::FOLDER_TAG)
                )
            );
        }

        $this->resyncRecipientsIfSiteNameChanged((array)$idsForSync);

        self::$parentPageId = null;
        self::$tagsForDelete = [];
    }

    /**
     * Refresh CleverReach segments, deactivates recipients from deleted folder and removes group tag from recipients
     * which belong to deleted groups
     *
     * @param int $pageId
     */
    private function handleFolderDeleteAfter($pageId)
    {
        $idsFromDeletedFolder = $this->getUserIdsByFolderId($pageId);

        $deletedGroups = $this->getUserGroupsRepository()->getUserGroupIdsByPageId($pageId);
        $tagsToDelete = array_column($deletedGroups, 'title');
        $idsFromDeletedGroup = [];
        foreach (array_column($deletedGroups, 'uid') as $groupId) {
            $idsFromDeletedGroup[] = $this->getUserRepository()->getUserIdsByGroupId($groupId);
        }

        $idsFromDeletedGroup = call_user_func_array('array_merge', $idsFromDeletedGroup);
        $idsForSync = array_unique(array_merge((array)$idsFromDeletedGroup, (array)$idsFromDeletedFolder));
        $this->enqueueTask(new FilterSyncTask());
        if (!empty($idsForSync)) {
            $this->enqueueTask(
                new RecipientSyncTask($idsForSync, $this->formatTags($tagsToDelete, RecipientsService::GROUP_TAG))
            );
        }

        $this->resyncRecipientsIfSiteNameChanged((array)$idsForSync);
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function getUserIdsByFolderId($id)
    {
        $pageIdsForFilter = [];

        if (self::$parentPageId !== 0) {
            $pageIdsForFilter[] = $id;
        } else {
            // if updated folder is root fetch all folder ids which belong to root and have users
            $pageIdsForFilter = $this->getPageRepository()->getUserFolderIdsFilteredByRootId($id);
        }

        return $this->getUserRepository()->getUserIdsByPageIds($pageIdsForFilter);
    }

    /**
     * @param int $id
     */
    private function handleUserGroupUpdatedAfter($id)
    {
        $this->enqueueTask(new FilterSyncTask());

        $idsForUpdate = $this->getUserRepository()->getUserIdsByGroupId($id);
        if (!empty($idsForUpdate)) {
            $this->enqueueTask(
                new RecipientSyncTask(
                    $idsForUpdate,
                    $this->formatTags(self::$tagsForDelete, RecipientsService::GROUP_TAG)
                )
            );
        }

        $this->resyncRecipientsIfSiteNameChanged($idsForUpdate);
        self::$tagsForDelete = [];
    }

    /**
     * @param array $idsToAvoid
     */
    private function resyncRecipientsIfSiteNameChanged(array $idsToAvoid = [])
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== $this->getConfigService()->getSiteName()) {
            $this->getConfigService()->saveSiteName();
            /** @var RecipientsService $recipientsService */
            $recipientsService = ServiceRegister::getService(Recipients::CLASS_NAME);
            $allIds = $recipientsService->getAllRecipientsIds();

            $this->enqueueTask(new RecipientSyncTask(array_diff($allIds, $idsToAvoid)));
        }
    }

    /**
     * @param string $email
     */
    private function enqueueRecipientDeactivateTask($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->enqueueTask(new RecipientDeactivateSyncTask([$email]));
        }
    }

    /**
     * Enqueues task if auth token id valid and if InitialSyncTask is completed
     *
     * @param Task $task
     */
    private function enqueueTask(Task $task)
    {
        if (empty($this->getConfigService()->getAccessToken())) {
            return;
        }

        try {
            $this->getQueueService()->enqueue($this->getConfigService()->getQueueName(), $task);
        } catch (QueueStorageUnavailableException $exception) {
            Logger::logError($exception->getMessage(), 'Integration');
        }
    }

    /**
     * @param array $sourceUserGroups
     * @param string $type
     *
     * @return \CleverReach\BusinessLogic\Entity\TagCollection
     */
    private function formatTags($sourceUserGroups, $type)
    {
        $tags = new TagCollection();
        foreach ($sourceUserGroups as $userGroup) {
            $tags->addTag(new Tag($userGroup, $type));
        }

        return $tags;
    }

    /**
     * @param array $newRow
     * @param array $origArray
     *
     * @return bool
     */
    private function isEmailChanged($newRow, $origArray)
    {
        return array_key_exists('email', $newRow) &&
            array_key_exists('email', $origArray) &&
            $origArray['email'] !== $newRow['email'];
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

    /**
     * @return Queue
     */
    private function getQueueService()
    {
        if ($this->queueService === null) {
            $this->queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queueService;
    }

    /**
     * @return FrontendUserGroupRepositoryInterface
     */
    private function getUserGroupsRepository()
    {
        if ($this->userGroupsRepository === null) {
            $this->userGroupsRepository = ServiceRegister::getService(FrontendUserGroupRepositoryInterface::class);
        }

        return $this->userGroupsRepository;
    }

    /**
     * @return FrontendUserRepositoryInterface
     */
    private function getUserRepository()
    {
        if ($this->userRepository === null) {
            $this->userRepository = ServiceRegister::getService(FrontendUserRepositoryInterface::class);
        }

        return $this->userRepository;
    }

    /**
     * @return \WebanUg\Cleverreach\Domain\Repository\Interfaces\PageRepositoryInterface
     */
    private function getPageRepository()
    {
        if ($this->pageRepository === null) {
            $this->pageRepository = ServiceRegister::getService(PageRepositoryInterface::class);
        }

        return $this->pageRepository;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDBConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
