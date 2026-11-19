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

    public function testFindSkipsKeysNotUsableForSignatureVerification(): void
    {
        [, $jwk] = $this->generateRsaJwk('kid-a');

        $this->assertSame($jwk, JwkConverter::find(['keys' => [$jwk]], 'kid-a', 'RS256'));
        $this->assertNull(JwkConverter::find(['keys' => [$jwk + ['use' => 'enc']]], 'kid-a', 'RS256'));
        $this->assertNotNull(JwkConverter::find(['keys' => [$jwk + ['use' => 'sig']]], 'kid-a', 'RS256'));
        $this->assertNull(JwkConverter::find(['keys' => [$jwk + ['key_ops' => ['encrypt']]]], 'kid-a', 'RS256'));
        $this->assertNull(JwkConverter::find(['keys' => [$jwk + ['key_ops' => 'verify']]], 'kid-a', 'RS256'));
        $this->assertNotNull(JwkConverter::find(['keys' => [$jwk + ['key_ops' => ['verify']]]], 'kid-a', 'RS256'));
        $this->assertNull(JwkConverter::find(['keys' => [$jwk]], 'kid-a', 'RS512'), 'The key declares alg RS256');
    }

    public function testFindPicksTheUsableKeyWhenSeveralShareAKid(): void
    {
        [, $encryption] = $this->generateRsaJwk('shared');
        [, $signing] = $this->generateRsaJwk('shared');
        $encryption['use'] = 'enc';

        $this->assertSame($signing, JwkConverter::find(['keys' => [$encryption, $signing]], 'shared', 'RS256'));
    }

    public function testFindMatchesEntraPerKeyIssuerAgainstTheTokenTenant(): void
    {
        [, $jwk] = $this->generateRsaJwk('kid-a');
        unset($jwk['alg']);
        $jwk['issuer'] = 'https://login.microsoftonline.com/{tenantid}/v2.0';
        $jwks = ['keys' => [$jwk]];
        $tenant = '00000000-0000-0000-0000-000000000001';
        $issuer = 'https://login.microsoftonline.com/' . $tenant . '/v2.0';

        $this->assertSame($jwk, JwkConverter::find($jwks, 'kid-a', 'RS256', $issuer, $tenant));
        $this->assertNull(JwkConverter::find($jwks, 'kid-a', 'RS256', $issuer, 'another-tenant'));
        $this->assertNull(JwkConverter::find($jwks, 'kid-a', 'RS256', $issuer, null));

        $jwk['issuer'] = $issuer;
        $this->assertNotNull(JwkConverter::find(['keys' => [$jwk]], 'kid-a', 'RS256', $issuer, null));
        $this->assertNull(JwkConverter::find(['keys' => [$jwk]], 'kid-a', 'RS256', 'https://evil.example', null));
    }
}
