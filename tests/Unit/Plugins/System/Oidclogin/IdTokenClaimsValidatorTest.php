<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use DateTimeImmutable;
use Joomla\Plugin\System\Oidclogin\Claims\IdTokenClaimsValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdTokenClaimsValidator::class)]
class IdTokenClaimsValidatorTest extends TestCase
{
    private const CLIENT = 'joomla';
    private const NONCE = 'n-0S6_WzA2Mj';

    /**
     * Claims shaped like lcobucci/jwt's claims()->all(): aud is a list, times are DateTimeImmutable.
     *
     * @return array<string, mixed>
     */
    private function claims(): array
    {
        return [
            'iss' => 'https://auth.dev.local:8443/realms/demo',
            'sub' => '248289761001',
            'aud' => [self::CLIENT],
            'exp' => new DateTimeImmutable('+5 minutes'),
            'iat' => new DateTimeImmutable(),
            'nonce' => self::NONCE,
        ];
    }

    public function testAcceptsCompleteClaims(): void
    {
        $this->assertNull(IdTokenClaimsValidator::validate($this->claims(), self::CLIENT, self::NONCE));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function requiredClaims(): array
    {
        return array_combine(
            ['iss', 'sub', 'aud', 'exp', 'iat', 'nonce'],
            [['iss'], ['sub'], ['aud'], ['exp'], ['iat'], ['nonce']]
        );
    }

    #[DataProvider('requiredClaims')]
    public function testRejectsAMissingRequiredClaim(string $claim): void
    {
        $claims = $this->claims();
        unset($claims[$claim]);

        $this->assertStringContainsString('"' . $claim . '"', (string) IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    #[DataProvider('requiredClaims')]
    public function testRejectsAnEmptyRequiredClaim(string $claim): void
    {
        $claims = $this->claims();
        $claims[$claim] = $claim === 'aud' ? [] : '';

        $this->assertNotNull(IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    public function testRejectsWhenNoNonceWasStoredForTheLogin(): void
    {
        $this->assertStringContainsString('No nonce stored', (string) IdTokenClaimsValidator::validate($this->claims(), self::CLIENT, ''));
    }

    public function testRejectsANonceMismatch(): void
    {
        $this->assertStringContainsString('nonce mismatch', (string) IdTokenClaimsValidator::validate($this->claims(), self::CLIENT, 'other'));
    }

    public function testRejectsANonStringSubject(): void
    {
        $claims = $this->claims();
        $claims['sub'] = 42;

        $this->assertNotNull(IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    public function testRejectsAnAudienceWithoutTheClient(): void
    {
        $claims = $this->claims();
        $claims['aud'] = ['someone-else'];

        $this->assertStringContainsString('audience', (string) IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    public function testRequiresAzpWhenThereAreSeveralAudiences(): void
    {
        $claims = $this->claims();
        $claims['aud'] = [self::CLIENT, 'api'];

        $this->assertStringContainsString('azp', (string) IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));

        $claims['azp'] = 'api';
        $this->assertStringContainsString('azp', (string) IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));

        $claims['azp'] = self::CLIENT;
        $this->assertNull(IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    public function testChecksAzpWheneverItIsPresent(): void
    {
        $claims = $this->claims();
        $claims['azp'] = 'other';

        $this->assertStringContainsString('azp', (string) IdTokenClaimsValidator::validate($claims, self::CLIENT, self::NONCE));
    }

    public function testUserInfoSubjectMustBePresentAndEqual(): void
    {
        $id = ['sub' => 'abc'];

        $this->assertNull(IdTokenClaimsValidator::validateUserInfoSubject($id, ['sub' => 'abc']));
        $this->assertStringContainsString('mismatch', (string) IdTokenClaimsValidator::validateUserInfoSubject($id, ['sub' => 'xyz']));
        $this->assertStringContainsString('Missing', (string) IdTokenClaimsValidator::validateUserInfoSubject($id, []));
        $this->assertStringContainsString('Missing', (string) IdTokenClaimsValidator::validateUserInfoSubject([], ['sub' => 'abc']));
        $this->assertStringContainsString('Missing', (string) IdTokenClaimsValidator::validateUserInfoSubject(['sub' => 1], ['sub' => 1]));
    }

    public function testMergeLetsUserInfoAddButNotReplaceIdTokenClaims(): void
    {
        $merged = IdTokenClaimsValidator::merge(
            ['sub' => 'abc', 'preferred_username' => 'alice', 'email' => 'alice@example.com'],
            ['sub' => 'abc', 'preferred_username' => 'admin', 'email' => 'admin@example.com', 'name' => 'Alice']
        );

        $this->assertSame('alice', $merged['preferred_username']);
        $this->assertSame('alice@example.com', $merged['email']);
        $this->assertSame('Alice', $merged['name']);
    }
}
