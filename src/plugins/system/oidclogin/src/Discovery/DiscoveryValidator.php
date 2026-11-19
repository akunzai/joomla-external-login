<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Discovery;

defined('_JEXEC') or die;

/**
 * Validates the configured issuer and the OIDC discovery document fetched from it
 * (OpenID Connect Discovery 1.0 §4.3), so the endpoints the plugin sends credentials
 * and tokens to are bound to the configured issuer.
 *
 * Every method returns null when valid, or a short English reason for the logs.
 *
 * @since 5.2.1
 */
final class DiscoveryValidator
{
    /**
     * Endpoints that must be present in the discovery document.
     */
    private const REQUIRED_ENDPOINTS = ['authorization_endpoint', 'token_endpoint', 'jwks_uri'];

    /**
     * Endpoints that are validated when present.
     */
    private const OPTIONAL_ENDPOINTS = ['userinfo_endpoint', 'end_session_endpoint'];

    /**
     * Hosts implicitly trusted for Microsoft Entra ID issuers (its UserInfo endpoint lives on Microsoft Graph).
     */
    private const ENTRA_ISSUER_HOST = 'login.microsoftonline.com';
    private const ENTRA_TRUSTED_HOSTS = ['graph.microsoft.com'];

    /**
     * Validate the issuer configured by the administrator.
     */
    public static function validateIssuer(string $issuer): ?string
    {
        if ($issuer === '') {
            return 'Issuer is not configured';
        }

        $reason = self::validateUrl($issuer);

        if ($reason !== null) {
            return 'Issuer ' . $reason;
        }

        if (parse_url($issuer, PHP_URL_QUERY) !== null) {
            return 'Issuer must not contain a query string';
        }

        return null;
    }

    /**
     * Validate a discovery document against the configured issuer.
     *
     * @param array<string, mixed> $document
     * @param string[] $trustedHosts additional hosts (lower-case) endpoints may live on
     */
    public static function validate(array $document, string $configuredIssuer, array $trustedHosts = []): ?string
    {
        $reason = self::validateIssuer($configuredIssuer);

        if ($reason !== null) {
            return $reason;
        }

        $issuer = $document['issuer'] ?? null;

        if (!is_string($issuer) || $issuer === '') {
            return 'Discovery document has no issuer';
        }

        // The trailing slash is tolerated because the configured value is used as a URL prefix.
        if (rtrim($issuer, '/') !== rtrim($configuredIssuer, '/')) {
            return 'Discovery issuer "' . $issuer . '" does not match the configured issuer';
        }

        $allowedHosts = self::allowedHosts($configuredIssuer, $trustedHosts);

        foreach (array_merge(self::REQUIRED_ENDPOINTS, self::OPTIONAL_ENDPOINTS) as $name) {
            $required = in_array($name, self::REQUIRED_ENDPOINTS, true);

            if (!array_key_exists($name, $document) || $document[$name] === null || $document[$name] === '') {
                if ($required) {
                    return 'Discovery document has no ' . $name;
                }

                continue;
            }

            if (!is_string($document[$name])) {
                return 'Discovery ' . $name . ' is not a string';
            }

            $reason = self::validateUrl($document[$name]);

            if ($reason !== null) {
                return 'Discovery ' . $name . ' ' . $reason;
            }

            $host = strtolower((string) parse_url($document[$name], PHP_URL_HOST));

            if (!in_array($host, $allowedHosts, true)) {
                return 'Discovery ' . $name . ' host "' . $host . '" is neither the issuer host nor a trusted endpoint host';
            }
        }

        return null;
    }

    /**
     * Parse the "trusted_endpoint_hosts" option: host names separated by commas, spaces or new lines.
     *
     * @return string[]
     */
    public static function parseTrustedHosts(string $value): array
    {
        $hosts = preg_split('/[\s,]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($hosts === false ? [] : $hosts));
    }

    /**
     * @param string[] $trustedHosts
     *
     * @return string[]
     */
    private static function allowedHosts(string $issuer, array $trustedHosts): array
    {
        $issuerHost = strtolower((string) parse_url($issuer, PHP_URL_HOST));
        $hosts = array_merge([$issuerHost], array_map('strtolower', $trustedHosts));

        if ($issuerHost === self::ENTRA_ISSUER_HOST) {
            $hosts = array_merge($hosts, self::ENTRA_TRUSTED_HOSTS);
        }

        return array_values(array_unique($hosts));
    }

    /**
     * Require an absolute https URL with a host and without credentials or fragment.
     */
    private static function validateUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'is not an absolute URL';
        }

        if (strtolower($parts['scheme']) !== 'https') {
            return 'must use https';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'must not contain credentials';
        }

        if (isset($parts['fragment']) || str_contains($url, '#')) {
            return 'must not contain a fragment';
        }

        return null;
    }
}
