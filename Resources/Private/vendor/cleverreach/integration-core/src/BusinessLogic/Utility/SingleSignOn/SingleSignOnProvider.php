<?php

namespace CleverReach\BusinessLogic\Utility\SingleSignOn;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;

/**
 * Class SingleSignOnProvider
 *
 * @package CleverReach\BusinessLogic\Utility
 */
class SingleSignOnProvider
{
    const CLASS_NAME = __CLASS__;

    const FALLBACK_URL = 'https://cleverreach.com/login';

    /**
     * Creates SSO link in format:
     * https://$client_id.$expanded_zone.cleverreach.com/admin/login.php?otp=$otp&oid=$oid&exp=$exp&ref=urlencode($deepLink).
     *
     * @param string $deepLink Address on which will system redirect after successful login.
     *
     * @return string
     *   SSO link.
     */
    public static function getUrl($deepLink)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $cleverreachToken = $configService->getAccessToken();
        $userInfo = $configService->getUserInfo();
        if (empty($userInfo['login_domain'])) {
            return static::FALLBACK_URL;
        }

        $params = array(
            'otp' => OtpProvider::generateOtp($cleverreachToken),
            'oid' => $configService->getClientId(),
            'exp' => self::getExpiryTimestamp($cleverreachToken),
            'ref' => $deepLink,
        );

        return 'https://' . $userInfo['login_domain'] . '/admin/login.php?' . http_build_query($params);
    }

    /**
     * Returns token expiry timestamp.
     *
     * @param string $cleverreachToken Token provided by CleverReach.
     *
     * @return int
     *   Token expiry timestamp.
     */
    private static function getExpiryTimestamp($cleverreachToken)
    {
        // cleverreachToken = header64encoded.body64encoded.cripto64encoded
        $tokenArray = explode('.', $cleverreachToken);
        $body64encoded = $tokenArray[1];
        $body = json_decode(base64_decode($body64encoded), true);

        return $body['exp'];
    }
}
