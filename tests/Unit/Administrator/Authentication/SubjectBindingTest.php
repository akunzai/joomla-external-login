<?php

namespace Tests\Unit\Administrator\Authentication;

use Joomla\Component\Externallogin\Administrator\Authentication\SubjectBinding;
use Joomla\Component\Externallogin\Administrator\Authentication\SubjectMatchOutcome;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubjectBinding::class)]
class SubjectBindingTest extends TestCase
{
    private const SERVER = 7;

    private const ISSUER = 'https://idp.example/realms/demo';

    public static function modeProvider(): array
    {
        return [
            'subject' => [['binding_mode' => 'subject'], true],
            'username' => [['binding_mode' => 'username'], false],
            'missing reads as username (server saved before 5.3.0)' => [[], false],
            'unknown value reads as username' => [['binding_mode' => 'other'], false],
        ];
    }

    #[DataProvider('modeProvider')]
    public function testIsSubjectMode(array $params, bool $expected): void
    {
        $this->assertSame($expected, SubjectBinding::isSubjectMode(new Registry($params)));
    }

    public function testFitsRejectsEmptyAndOverlongValuesInsteadOfTruncating(): void
    {
        $this->assertTrue(SubjectBinding::fits(self::ISSUER, str_repeat('s', 255)));
        $this->assertFalse(SubjectBinding::fits(self::ISSUER, str_repeat('s', 256)));
        $this->assertFalse(SubjectBinding::fits(str_repeat('i', 256), 'sub'));
        $this->assertFalse(SubjectBinding::fits('', 'sub'));
        $this->assertFalse(SubjectBinding::fits(self::ISSUER, ''));
    }

    public function testAccountBoundToTheSubjectWinsEvenWhenTheUsernameClaimPointsElsewhere(): void
    {
        // The username claim changed at the provider and now names another account (42).
        $match = SubjectBinding::resolve(self::SERVER, 10, 42, ['server_id' => self::SERVER, 'issuer' => self::ISSUER, 'subject' => 'other']);

        $this->assertSame(SubjectMatchOutcome::Bound, $match->outcome);
        $this->assertSame(10, $match->userId);
    }

    public function testAccountBoundToThisServerWithoutASubjectRecordsItOnFirstUse(): void
    {
        $match = SubjectBinding::resolve(self::SERVER, null, 10, ['server_id' => self::SERVER, 'issuer' => null, 'subject' => null]);

        $this->assertSame(SubjectMatchOutcome::FirstUse, $match->outcome);
        $this->assertSame(10, $match->userId);
    }

    public function testDifferentSubjectPresentingABoundAccountsUsernameIsDenied(): void
    {
        $match = SubjectBinding::resolve(self::SERVER, null, 10, ['server_id' => self::SERVER, 'issuer' => self::ISSUER, 'subject' => 'victim']);

        $this->assertSame(SubjectMatchOutcome::Mismatch, $match->outcome);
    }

    public function testChangedIssuerIsDeniedRatherThanRebound(): void
    {
        // Same sub under the old issuer: the lookup by (server, new issuer, sub) found nothing.
        $match = SubjectBinding::resolve(self::SERVER, null, 10, ['server_id' => self::SERVER, 'issuer' => 'https://old.example', 'subject' => 'abc']);

        $this->assertSame(SubjectMatchOutcome::Mismatch, $match->outcome);
    }

    public static function existingRulesProvider(): array
    {
        return [
            'no account with that username (auto-register or deny)' => [0, null],
            'account with no binding (not activated)' => [10, null],
            'account bound to another server (ADR 0001)' => [10, ['server_id' => 99, 'issuer' => null, 'subject' => null]],
        ];
    }

    #[DataProvider('existingRulesProvider')]
    public function testFallsBackToTheExistingRules(int $usernameUserId, ?array $binding): void
    {
        $match = SubjectBinding::resolve(self::SERVER, null, $usernameUserId, $binding);

        $this->assertSame(SubjectMatchOutcome::None, $match->outcome);
        $this->assertSame($usernameUserId, $match->userId);
    }
}
