<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use Joomla\Plugin\System\Oidclogin\Discovery\DiscoveryValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiscoveryValidator::class)]
class DiscoveryValidatorTest extends TestCase
{
    private const ISSUER = 'https://auth.dev.local:8443/realms/demo';

    /**
     * @return array<string, mixed>
     */
    private function keycloakDocument(): array
    {
        $base = self::ISSUER . '/protocol/openid-connect';

        return [
            'issuer' => self::ISSUER,
            'authorization_endpoint' => $base . '/auth',
            'token_endpoint' => $base . '/token',
            'userinfo_endpoint' => $base . '/userinfo',
            'jwks_uri' => $base . '/certs',
            'end_session_endpoint' => $base . '/logout',
        ];
    }

    public function testAcceptsAKeycloakDocumentWithSameHostHttpsEndpoints(): void
    {
        $this->assertNull(DiscoveryValidator::validate($this->keycloakDocument(), self::ISSUER));
    }

    public function testToleratesATrailingSlashDifferenceOnTheIssuer(): void
    {
        $document = $this->keycloakDocument();
        $document['issuer'] = self::ISSUER . '/';

        $this->assertNull(DiscoveryValidator::validate($document, self::ISSUER));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidIssuers(): array
    {
        return [
            'empty' => ['', 'not configured'],
            'http' => ['http://auth.dev.local/realms/demo', 'https'],
            'relative' => ['/realms/demo', 'absolute'],
            'credentials' => ['https://user:pass@auth.dev.local/realms/demo', 'credentials'],
            'fragment' => ['https://auth.dev.local/realms/demo#x', 'fragment'],
            'query' => ['https://auth.dev.local/realms/demo?x=1', 'query'],
        ];
    }

    #[DataProvider('invalidIssuers')]
    public function testRejectsAnInvalidConfiguredIssuer(string $issuer, string $reason): void
    {
        $this->assertStringContainsString($reason, (string) DiscoveryValidator::validateIssuer($issuer));
        $this->assertNotNull(DiscoveryValidator::validate($this->keycloakDocument(), $issuer));
    }

    public function testRejectsADocumentWhoseIssuerDiffersFromTheConfiguredIssuer(): void
    {
        $document = $this->keycloakDocument();
        $document['issuer'] = 'https://auth.dev.local:8443/realms/other';

        $this->assertStringContainsString('does not match', (string) DiscoveryValidator::validate($document, self::ISSUER));
    }

    public function testRejectsADocumentWithoutIssuer(): void
    {
        $document = $this->keycloakDocument();
        unset($document['issuer']);

        $this->assertStringContainsString('no issuer', (string) DiscoveryValidator::validate($document, self::ISSUER));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function requiredEndpoints(): array
    {
        return [
            'authorization_endpoint' => ['authorization_endpoint'],
            'token_endpoint' => ['token_endpoint'],
            'jwks_uri' => ['jwks_uri'],
        ];
    }

    #[DataProvider('requiredEndpoints')]
    public function testRejectsADocumentMissingARequiredEndpoint(string $endpoint): void
    {
        $document = $this->keycloakDocument();
        unset($document[$endpoint]);

        $this->assertStringContainsString($endpoint, (string) DiscoveryValidator::validate($document, self::ISSUER));
    }

    public function testOptionalEndpointsMayBeAbsent(): void
    {
        $document = $this->keycloakDocument();
        unset($document['userinfo_endpoint'], $document['end_session_endpoint']);

        $this->assertNull(DiscoveryValidator::validate($document, self::ISSUER));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidEndpoints(): array
    {
        return [
            'http token endpoint' => ['token_endpoint', 'http://auth.dev.local:8443/realms/demo/token', 'https'],
            'foreign token endpoint' => ['token_endpoint', 'https://evil.example/token', 'trusted endpoint host'],
            'credentials in jwks_uri' => ['jwks_uri', 'https://u:p@auth.dev.local:8443/certs', 'credentials'],
            'fragment in authorization endpoint' => ['authorization_endpoint', 'https://auth.dev.local:8443/auth#x', 'fragment'],
            'foreign userinfo endpoint' => ['userinfo_endpoint', 'https://evil.example/userinfo', 'trusted endpoint host'],
            'foreign end_session endpoint' => ['end_session_endpoint', 'https://evil.example/logout', 'trusted endpoint host'],
            'relative endpoint' => ['token_endpoint', '/token', 'absolute'],
        ];
    }

    #[DataProvider('invalidEndpoints')]
    public function testRejectsAnInvalidEndpoint(string $endpoint, string $url, string $reason): void
    {
        $document = $this->keycloakDocument();
        $document[$endpoint] = $url;

        $result = (string) DiscoveryValidator::validate($document, self::ISSUER);

        $this->assertStringContainsString($endpoint, $result);
        $this->assertStringContainsString($reason, $result);
    }

    public function testRejectsANonStringEndpoint(): void
    {
        $document = $this->keycloakDocument();
        $document['token_endpoint'] = ['https://auth.dev.local:8443/token'];

        $this->assertStringContainsString('not a string', (string) DiscoveryValidator::validate($document, self::ISSUER));
    }

    public function testAcceptsEndpointsOnTrustedHosts(): void
    {
        $issuer = 'https://accounts.google.com';
        $document = [
            'issuer' => $issuer,
            'authorization_endpoint' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_endpoint' => 'https://oauth2.googleapis.com/token',
            'userinfo_endpoint' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'jwks_uri' => 'https://www.googleapis.com/oauth2/v3/certs',
        ];

        $this->assertNotNull(DiscoveryValidator::validate($document, $issuer));

        $trusted = DiscoveryValidator::parseTrustedHosts("oauth2.googleapis.com, OpenIDConnect.googleapis.com\nwww.googleapis.com");
        $this->assertNull(DiscoveryValidator::validate($document, $issuer, $trusted));
    }

    public function testTrustsMicrosoftGraphOnlyForEntraIssuers(): void
    {
        $issuer = 'https://login.microsoftonline.com/00000000-0000-0000-0000-000000000001/v2.0';
        $document = [
            'issuer' => $issuer,
            'authorization_endpoint' => 'https://login.microsoftonline.com/00000000-0000-0000-0000-000000000001/oauth2/v2.0/authorize',
            'token_endpoint' => 'https://login.microsoftonline.com/00000000-0000-0000-0000-000000000001/oauth2/v2.0/token',
            'userinfo_endpoint' => 'https://graph.microsoft.com/oidc/userinfo',
            'jwks_uri' => 'https://login.microsoftonline.com/00000000-0000-0000-0000-000000000001/discovery/v2.0/keys',
        ];

        $this->assertNull(DiscoveryValidator::validate($document, $issuer));

        $other = $this->keycloakDocument();
        $other['userinfo_endpoint'] = 'https://graph.microsoft.com/oidc/userinfo';
        $this->assertNotNull(DiscoveryValidator::validate($other, self::ISSUER));
    }

    public function testParseTrustedHostsSplitsLowercasesAndDeduplicates(): void
    {
        $this->assertSame(['a.example', 'b.example'], DiscoveryValidator::parseTrustedHosts(" A.example,b.example\n\na.example "));
        $this->assertSame([], DiscoveryValidator::parseTrustedHosts(''));
    }
}
