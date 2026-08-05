<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use Joomla\Plugin\System\Oidclogin\Jwk\JwkConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JwkConverter::class)]
class JwkConverterTest extends TestCase
{
    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function generateRsaJwk(string $kid = 'test-kid'): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKeyPem);
        $details = openssl_pkey_get_details($resource);

        $jwk = [
            'kty' => 'RSA',
            'kid' => $kid,
            'alg' => 'RS256',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ];

        return [$privateKeyPem, $jwk];
    }

    public function testConvertsRsaJwkIntoAPemKeyThatVerifiesASignatureFromTheMatchingPrivateKey(): void
    {
        [$privateKeyPem, $jwk] = $this->generateRsaJwk();

        $key = JwkConverter::toKey($jwk);
        $this->assertNotNull($key);

        $signature = '';
        openssl_sign('payload', $signature, $privateKeyPem, OPENSSL_ALGO_SHA256);

        $this->assertSame(1, openssl_verify('payload', $signature, $key->contents(), OPENSSL_ALGO_SHA256));
    }

    public function testRejectsASignatureFromADifferentPrivateKey(): void
    {
        [, $jwk] = $this->generateRsaJwk();
        [$otherPrivateKeyPem] = $this->generateRsaJwk();

        $key = JwkConverter::toKey($jwk);
        $this->assertNotNull($key);

        $signature = '';
        openssl_sign('payload', $signature, $otherPrivateKeyPem, OPENSSL_ALGO_SHA256);

        $this->assertSame(0, openssl_verify('payload', $signature, $key->contents(), OPENSSL_ALGO_SHA256));
    }

    public function testReturnsNullForANonRsaKeyType(): void
    {
        $this->assertNull(JwkConverter::toKey(['kty' => 'EC', 'n' => 'x', 'e' => 'y']));
    }

    public function testReturnsNullWhenModulusOrExponentIsMissing(): void
    {
        $this->assertNull(JwkConverter::toKey(['kty' => 'RSA', 'e' => 'AQAB']));
        $this->assertNull(JwkConverter::toKey(['kty' => 'RSA', 'n' => 'abc']));
    }

    public function testFindReturnsTheMatchingJwkByKid(): void
    {
        [, $jwkA] = $this->generateRsaJwk('kid-a');
        [, $jwkB] = $this->generateRsaJwk('kid-b');
        $jwks = ['keys' => [$jwkA, $jwkB]];

        $this->assertSame($jwkB, JwkConverter::find($jwks, 'kid-b'));
    }

    public function testFindReturnsNullWhenKidIsMissingOrNotFound(): void
    {
        [, $jwkA] = $this->generateRsaJwk('kid-a');
        $jwks = ['keys' => [$jwkA]];

        $this->assertNull(JwkConverter::find($jwks, null));
        $this->assertNull(JwkConverter::find($jwks, ''));
        $this->assertNull(JwkConverter::find($jwks, 'missing'));
    }
}
