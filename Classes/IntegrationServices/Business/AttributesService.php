<?php

namespace CR\OfficialCleverreach\IntegrationServices\Business;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\RecipientAttribute;
use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CR\OfficialCleverreach\IntegrationServices\Infrastructure\ConfigurationService;
use CR\OfficialCleverreach\Utility\Helper;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class AttributesService implements Attributes
{
    /**
     * @var ConfigurationService
     */
    private $configService;

    /**
     * @var array $attributes
     */
    private static $defaultAttributes = [
        'email' => [
            'description' => 'Email',
            'key' => 'tx_officialcleverreach_email',
        ],
        'firstname' => [
            'description' => 'First Name',
            'key' => 'tx_officialcleverreach_first_name',
        ],
        'lastname' => [
            'description' => 'Last Name',
            'key' => 'tx_officialcleverreach_last_name',
        ],
        'title' => [
            'description' => 'Title',
            'key' => 'tx_officialcleverreach_title',
        ],
        'birthday' => [
            'description' => 'Date of Birth',
            'key' => 'tx_officialcleverreach_birthday',
        ],
        'shop' => [
            'description' => 'CMS',
            'key' => 'tx_officialcleverreach_shop',
        ],
        'street' => [
            'description' => 'Address',
            'key' => 'tx_officialcleverreach_address',
        ],
        'zip' => [
            'description' => 'Zipcode',
            'key' => 'tx_officialcleverreach_zip',
        ],
        'city' => [
            'description' => 'City',
            'key' => 'tx_officialcleverreach_city',
        ],
        'company' => [
            'description' => 'Company',
            'key' => 'tx_officialcleverreach_company',
        ],
        'state' => [
            'description' => 'State/Province',
            'key' => 'tx_officialcleverreach_state',
        ],
        'country' => [
            'description' => 'Country',
            'key' => 'tx_officialcleverreach_country',
        ],
        'phone' => [
            'description' => 'Phone',
            'key' => 'tx_officialcleverreach_phone',
        ],
        'newsletter' => [
            'description' => 'Subscribe to newsletter',
            'key' => 'cr_newsletter_subscription'
        ],
    ];

    /**
     * @var array
     */
    private static $additionalAttributes = [
        'salutation' => [
            'description' => 'Salutation',
            'key' => 'tx_officialcleverreach_salutation',
        ],
        'customernumber' => [
            'description' => 'Customer Number',
            'key' => 'tx_officialcleverreach_customer_number',
        ],
        'language' => [
            'description' => 'Language',
            'key' => 'tx_officialcleverreach_language',
        ]

    ];

    /**
     * Get attributes from integration with translated params in system language.
     *
     * It should set name, description, preview_value and default_value for each attribute available in system.
     *
     * @return RecipientAttribute[]
     *   List of available attributes in the system.
     */
    public function getAttributes()
    {
        $defaultAttributes = $this->formatAttributes();

        return ExtensionManagementUtility::isLoaded('sr_feuser_register')
            ? array_merge($defaultAttributes, $this->formatAttributes(false)) : $defaultAttributes;
    }

    public function getRecipientAttributes(Recipient $recipient)
    {
        return $this->getAttributes();
    }

    /**
     * Creates array of recipient attributes
     *
     * @param bool $useDefaultAttributes flag weather to use default or additional attributes
     *
     * @return RecipientAttribute[]
     */
    private function formatAttributes($useDefaultAttributes = true)
    {
        $attributesForSync = $useDefaultAttributes ? self::$defaultAttributes : self::$additionalAttributes;
        $attributes = [];
        foreach ($attributesForSync as $attributeName => $attribute) {
            $recipientAttribute = new RecipientAttribute($attributeName);
            $recipientAttribute->setDescription($this->getAttributeDescription($attribute));

            $attributes[] = $recipientAttribute;
        }

        return $attributes;
    }

    /**
     * Returns translated attribute description
     *
     * @param array $attribute in format ['description' => $description, 'key' => $key]
     *
     * @return string
     */
    private function getAttributeDescription(array $attribute)
    {
        if (empty($GLOBALS['TSFE'])) {
            return Helper::getTranslation($attribute['key'], $attribute['description'], $this->getConfigService()->getUserLanguage())
                ?: $attribute['description'];
        }

        $GLOBALS['TSFE']->config['config']['language'] = $this->getConfigService()->getUserLanguage();

        return Helper::getTranslation($attribute['key'], $attribute['description'])
            ?: $attribute['description'];
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