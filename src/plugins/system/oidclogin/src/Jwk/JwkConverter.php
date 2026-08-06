<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Jwk;

defined('_JEXEC') or die;

use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Converts entries from a JSON Web Key Set (JWKS) document into Lcobucci\JWT
 * signer keys, without requiring a dedicated JWK library dependency.
 *
 * @since 5.2.0
 */
final class JwkConverter
{
    /**
     * Find the JWK matching the given key id within a decoded JWKS document.
     *
     * @param array<string, mixed> $jwks The decoded JWKS document (a "keys" array)
     * @param string|null $kid The key id to look for
     *
     * @return array<string, mixed>|null The matching JWK, or null when not found
     */
    public static function find(array $jwks, ?string $kid): ?array
    {
        if ($kid === null || $kid === '') {
            return null;
        }

        foreach ((array) ($jwks['keys'] ?? []) as $jwk) {
            if (is_array($jwk) && ($jwk['kid'] ?? null) === $kid) {
                return $jwk;
            }
        }

        return null;
    }

    /**
     * Convert an RSA JWK into a PEM-encoded public key usable as a
     * Lcobucci\JWT verification key.
     *
     * @param array<string, mixed> $jwk A single JWK entry
     */
    public static function toKey(array $jwk): ?Key
    {
        if (($jwk['kty'] ?? null) !== 'RSA' || empty($jwk['n']) || empty($jwk['e'])) {
            return null;
        }

        $modulus = self::base64UrlDecode((string) $jwk['n']);
        $exponent = self::base64UrlDecode((string) $jwk['e']);

        if ($modulus === false || $exponent === false || $modulus === '' || $exponent === '') {
            return null;
        }

        $rsaPublicKey = self::encodeSequence(self::encodeUnsignedInteger($modulus) . self::encodeUnsignedInteger($exponent));

        // AlgorithmIdentifier DER encoding for rsaEncryption (OID 1.2.840.113549.1.1.1) with NULL parameters.
        $algorithm = pack('H*', '300d06092a864886f70d0101010500');
        $bitString = "\x00" . $rsaPublicKey;
        $bitStringEncoded = "\x03" . self::encodeLength(strlen($bitString)) . $bitString;

        $subjectPublicKeyInfo = self::encodeSequence($algorithm . $bitStringEncoded);

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";

        return InMemory::plainText($pem);
    }

    private static function base64UrlDecode(string $data): string|false
    {
        $padded = strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4);

        return base64_decode($padded, true);
    }

    /**
     * DER-encode an unsigned big-endian integer (ASN.1 INTEGER, tag 0x02).
     */
    private static function encodeUnsignedInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");

        if ($bytes === '') {
            $bytes = "\x00";
        }

        // Prepend a zero byte when the high bit is set, so the integer isn't read as negative.
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . self::encodeLength(strlen($bytes)) . $bytes;
    }

    /**
     * DER-encode a SEQUENCE (tag 0x30) wrapping the given contents.
     */
    private static function encodeSequence(string $contents): string
    {
        return "\x30" . self::encodeLength(strlen($contents)) . $contents;
    }

    /**
     * DER-encode a length in the shortest definite form.
     */
    private static function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\x00");

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}
