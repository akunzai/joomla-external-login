<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Claims;

defined('_JEXEC') or die;

/**
 * ID Token claim checks required by OpenID Connect Core §3.1.3.7 that the JWT library's
 * constraints leave optional (presence of exp/iat/sub, nonce, azp), plus the rules for
 * combining ID Token and UserInfo claims (§5.3.2).
 *
 * Signature, iss, aud and time-window checks stay with the lcobucci/jwt validator; this
 * class only makes sure the claims those checks depend on cannot simply be omitted.
 *
 * Every validate method returns null when valid, or a short English reason for the logs.
 *
 * @since 5.2.1
 */
final class IdTokenClaimsValidator
{
    private const REQUIRED_CLAIMS = ['iss', 'sub', 'aud', 'exp', 'iat', 'nonce'];

    /**
     * @param array<string, mixed> $claims decoded ID Token claims
     */
    public static function validate(array $claims, string $clientId, string $expectedNonce): ?string
    {
        if ($expectedNonce === '') {
            return 'No nonce stored for this login';
        }

        foreach (self::REQUIRED_CLAIMS as $name) {
            if (!array_key_exists($name, $claims) || $claims[$name] === null || $claims[$name] === '' || $claims[$name] === []) {
                return 'ID token has no "' . $name . '" claim';
            }
        }

        if (!is_string($claims['sub'])) {
            return 'ID token "sub" claim is not a string';
        }

        if (!is_string($claims['nonce']) || !hash_equals($expectedNonce, $claims['nonce'])) {
            return 'ID token nonce mismatch';
        }

        $audiences = is_array($claims['aud']) ? array_values($claims['aud']) : [$claims['aud']];

        if (!in_array($clientId, $audiences, true)) {
            return 'ID token audience does not include the client';
        }

        // §3.1.3.7 (4)/(5): with several audiences, or whenever azp is present, it must be this client.
        if (count($audiences) > 1 || array_key_exists('azp', $claims)) {
            if (($claims['azp'] ?? null) !== $clientId) {
                return 'ID token "azp" claim does not match the client';
            }
        }

        return null;
    }

    /**
     * §5.3.2: the UserInfo "sub" must be present and exactly equal to the ID Token "sub".
     *
     * @param array<string, mixed> $idTokenClaims
     * @param array<string, mixed> $userInfoClaims
     */
    public static function validateUserInfoSubject(array $idTokenClaims, array $userInfoClaims): ?string
    {
        $idSub = $idTokenClaims['sub'] ?? null;
        $userInfoSub = $userInfoClaims['sub'] ?? null;

        if (!is_string($idSub) || $idSub === '' || !is_string($userInfoSub) || $userInfoSub === '') {
            return 'Missing "sub" in ID token or UserInfo response';
        }

        if (!hash_equals($idSub, $userInfoSub)) {
            return 'UserInfo "sub" mismatch';
        }

        return null;
    }

    /**
     * Merge UserInfo claims into the ID Token claims. UserInfo may add claims, but
     * never replace one the signed ID Token already carries.
     *
     * @param array<string, mixed> $idTokenClaims
     * @param array<string, mixed> $userInfoClaims
     *
     * @return array<string, mixed>
     */
    public static function merge(array $idTokenClaims, array $userInfoClaims): array
    {
        return $idTokenClaims + $userInfoClaims;
    }
}
