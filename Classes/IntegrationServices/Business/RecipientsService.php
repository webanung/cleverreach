<?php

namespace CR\OfficialCleverreach\IntegrationServices\Business;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserGroupRepositoryInterface;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\FrontendUserRepositoryInterface;
use CR\OfficialCleverreach\Domain\Repository\Interfaces\PageRepositoryInterface;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecipientsService implements Recipients
{
    const GROUP_TAG = 'Group';
    const FOLDER_TAG = 'Folder';

    /**
     * @var PageRepositoryInterface $crPageRepository
     */
    private $crPageRepository;
    /**
     * @var FrontendUserRepositoryInterface $crFrontendUserRepository
     */
    private $crFrontendUserRepository;
    /**
     * @var FrontendUserGroupRepositoryInterface $crFrontendUserGroupRepository
     */
    private $crFrontendUserGroupRepository;

    /**
     * RecipientsService constructor.
     */
    public function __construct()
    {
        $this->crPageRepository = ServiceRegister::getService(PageRepositoryInterface::class);
        $this->crFrontendUserRepository = ServiceRegister::getService(FrontendUserRepositoryInterface::class);
        $this->crFrontendUserGroupRepository = ServiceRegister::getService(FrontendUserGroupRepositoryInterface::class);
    }

    /**
     * @return TagCollection
     */
    public function getAllTags()
    {
        return $this->getTagsFormatted($this->crPageRepository->getAllUserFolders(), self::FOLDER_TAG)
            ->add($this->getTagsFormatted($this->crFrontendUserGroupRepository->getAllUserGroups(), self::GROUP_TAG));
    }

    /**
     * @return SpecialTagCollection
     */
    public function getAllSpecialTags()
    {
        return new SpecialTagCollection([SpecialTag::subscriber(), SpecialTag::contact()]);
    }

    /**
     * @param array $batchRecipientIds
     * @param bool $includeOrders
     *
     * @return Recipient[]
     *
     * @throws \Exception
     */
    public function getRecipientsWithTags(array $batchRecipientIds, $includeOrders)
    {
        $sourceRecipients = $this->crFrontendUserRepository->getUsersByIds($batchRecipientIds);
        $formattedRecipients = [];
        foreach ($sourceRecipients as $sourceUser) {
            if (!empty($sourceUser)) {
                $formattedRecipients[] = $this->createRecipient($sourceUser);
            }
        }

        return $formattedRecipients;
    }

    /**
     * @return array|null
     */
    public function getAllRecipientsIds()
    {
        return $this->crFrontendUserRepository->getAllIds();
    }

    /**
     * @param array $recipientIds
     */
    public function recipientSyncCompleted(array $recipientIds)
    {
        // Intentionally left empty. We do not need this functionality.
    }

    /**
     * @param array $sourceUser
     *
     * @return Recipient
     *
     * @throws \Exception
     */
    private function createRecipient($sourceUser)
    {
        $formattedRecipient = new Recipient($sourceUser['email']);
        $formattedRecipient->setFirstName(Helper::getValueIfNotEmpty('first_name', $sourceUser));
        $formattedRecipient->setLastName(Helper::getValueIfNotEmpty('last_name', $sourceUser));
        if (!empty($sourceUser['middle_name'])) {
            $formattedRecipient->setLastName($sourceUser['middle_name'] . ' ' . $formattedRecipient->getLastName());
        }

        $formattedRecipient->setTitle(Helper::getValueIfNotEmpty('title', $sourceUser));
        $formattedRecipient->setSource(GeneralUtility::locationHeaderUrl('/'));
        $formattedRecipient->setCompany(Helper::getValueIfNotEmpty('company', $sourceUser));
        $formattedRecipient->setShop($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $formattedRecipient->setZip(Helper::getValueIfNotEmpty('zip', $sourceUser));
        $formattedRecipient->setCity(Helper::getValueIfNotEmpty('city', $sourceUser));
        $formattedRecipient->setCountry($this->getCountry($sourceUser));
        $formattedRecipient->setStreet(Helper::getValueIfNotEmpty('address', $sourceUser));
        $formattedRecipient->setPhone(Helper::getValueIfNotEmpty('telephone', $sourceUser));
        $this->setNewsletterAndActiveStatus($sourceUser, $formattedRecipient);
        $this->setExtensionFields($formattedRecipient, $sourceUser);

        $timestamp = !empty($sourceUser['tstamp']) ? $sourceUser['tstamp'] : time();
        $formattedRecipient->setRegistered(new \DateTime('@' . $timestamp));

        $formattedRecipient->setTags($this->getUserTags($sourceUser['usergroup'], $sourceUser['pid']));
        $this->setSpecialTags($formattedRecipient);

        return $formattedRecipient;
    }

    /**
     * @param array $sourceUser
     *
     * @return string
     */
    private function getCountry($sourceUser)
    {
        if (!empty($sourceUser['country'])) {
            return $sourceUser['country'];
        }

        if (!empty($sourceUser['static_info_country'])) {
            return Helper::getCountryNameByIso3($sourceUser['static_info_country']);
        }

        return '';
    }

    /**
     * @param string $userGroups
     * @param int|string $pageId
     *
     * @return \CleverReach\BusinessLogic\Entity\TagCollection
     */
    private function getUserTags($userGroups, $pageId)
    {
        $groupTags = $this->getTagsFormatted(
            $this->crFrontendUserGroupRepository->getGroupTitleByUid($userGroups),
            self::GROUP_TAG
        );

        return $groupTags->add(
            $this->getTagsFormatted($this->crPageRepository->getUserFolderNameWithItsRoot($pageId), self::FOLDER_TAG)
        );
    }

    /**
     * @param array $data
     * @param string $type
     *
     * @return TagCollection
     */
    private function getTagsFormatted($data, $type)
    {
        $tags = new TagCollection();
        if (!is_array($data)) {
            return $tags;
        }

        foreach ($data as $item) {
            $tags->addTag(new Tag($item, $type));
        }

        return $tags;
    }

    /**
     * @param Recipient $formattedRecipient
     * @param array $sourceUser
     *
     * @throws \Exception
     */
    private function setExtensionFields(Recipient $formattedRecipient, $sourceUser)
    {
        $formattedRecipient->setLanguage(Helper::getValueIfNotEmpty('language', $sourceUser));
        $formattedRecipient->setCustomerNumber(Helper::getValueIfNotEmpty('cnum', $sourceUser));
        if (!empty($sourceUser['date_of_birth'])) {
            $date = new \DateTime('@' . $sourceUser['date_of_birth']);
            $formattedRecipient->setBirthday($date);
        }

        if (!empty($formattedRecipient->getStreet())) {
            // add house number
            $formattedRecipient->setStreet(
                trim(
                    $formattedRecipient->getStreet() . ' ' .
                    Helper::getValueIfNotEmpty('house_no', $sourceUser)
                )
            );
        }

        $formattedRecipient->setState(Helper::getValueIfNotEmpty('zone', $sourceUser));
        $this->setSalutation($formattedRecipient, $sourceUser);
    }

    /**
     * @param array $sourceUser
     * @param Recipient $formattedRecipient
     */
    private function setNewsletterAndActiveStatus(array $sourceUser, Recipient $formattedRecipient)
    {
        $newsletterStatus = $this->getNewsletterStatus($sourceUser);
        $deleted = (int)Helper::getValueIfNotEmpty('deleted', $sourceUser);
        $formattedRecipient->setNewsletterSubscription($newsletterStatus);
        $formattedRecipient->setActive(!$deleted && $newsletterStatus);
    }

    /**
     * @param array $sourceUser
     *
     * @return bool
     */
    private function getNewsletterStatus($sourceUser)
    {
        if (isset($sourceUser['cr_newsletter_subscription'])) {
            return (bool)$sourceUser['cr_newsletter_subscription'];
        }

        return false;
    }

    /**
     * @param Recipient $formattedRecipient
     */
    private function setSpecialTags(Recipient $formattedRecipient)
    {
        $specialTags = new SpecialTagCollection([SpecialTag::contact()]);
        if ($formattedRecipient->getNewsletterSubscription()) {
            $specialTags->addTag(SpecialTag::subscriber());
        }

        $formattedRecipient->setSpecialTags($specialTags);
    }

    /**
     * @param Recipient $formattedRecipient
     * @param array $sourceUser
     */
    private function setSalutation(Recipient $formattedRecipient, $sourceUser)
    {
        if (!array_key_exists('gender', $sourceUser)) {
            return;
        }

        $gender = (int)$sourceUser['gender'];
        if ($gender === 0) {
            $formattedRecipient->setSalutation('Mr');
        } elseif ($gender === 1) {
            $formattedRecipient->setSalutation('Ms');
        }
    }
}
