<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\Component\Externallogin\Administrator\Authentication\ExternalAuthenticationResponse;
use Joomla\Event\Event;
use Joomla\Plugin\System\Oidclogin\Extension\Oidclogin;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Exercises Oidclogin::onExternalLogin() — the dot-path claims-extraction
 * logic that turns merged ID Token + UserInfo claims into an
 * AuthenticationResponse.
 *
 * Constructed via ReflectionClass::newInstanceWithoutConstructor() to skip
 * CMSPlugin's language-loading/log-registration constructor, and with
 * $claims/$server pre-seeded directly — no live IdP, HTTP, or JWT
 * verification runs as part of this test.
 */
#[CoversClass(Oidclogin::class)]
class OidcloginTest extends TestCase
{
    /**
     * Builds an Oidclogin instance with $claims/$server pre-set via
     * reflection, ready to call onExternalLogin() on directly.
     *
     * @param array<string, mixed> $claims
     * @param array<string, mixed> $params
     */
    private function createPlugin(array $claims, array $params, int $serverId = 5): Oidclogin
    {
        $reflection = new ReflectionClass(Oidclogin::class);
        $plugin = $reflection->newInstanceWithoutConstructor();

        $server = (object) ['id' => $serverId, 'params' => new Registry($params)];

        foreach (['claims' => $claims, 'server' => $server] as $property => $value) {
            $reflectionProperty = $reflection->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($plugin, $value);
        }

        return $plugin;
    }

    private function dispatch(Oidclogin $plugin, AuthenticationResponse $response): Event
    {
        $event = new Event('onExternalLogin', ['response' => $response]);
        $plugin->onExternalLogin($event);

        return $event;
    }

    private const BASE_CLAIMS = [
        'sub' => 'abc123',
        'preferred_username' => 'alice',
        'email' => 'alice@example.com',
        'name' => 'Alice Example',
    ];

    private const BASE_PARAMS = [
        'username_claim' => 'preferred_username',
        'email_claim' => 'email',
        'name_claim' => 'name',
    ];

    public function testExtractsUsernameEmailAndFullnameFromTheMergedClaims(): void
    {
        $plugin = $this->createPlugin(self::BASE_CLAIMS, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertSame('alice', $response->username);
        $this->assertSame('alice@example.com', $response->email);
        $this->assertSame('Alice Example', $response->fullname);
        $this->assertSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertSame('system.oidclogin', $response->type);
        $this->assertContains(true, $event->getArgument('result', []));
    }

    public function testResolvesNestedDotPathClaims(): void
    {
        $claims = [
            'identity' => ['username' => 'bob', 'contact' => ['email' => 'bob@example.com']],
            'name' => 'Bob Example',
        ];
        $params = [
            'username_claim' => 'identity.username',
            'email_claim' => 'identity.contact.email',
            'name_claim' => 'name',
        ];

        $plugin = $this->createPlugin($claims, $params);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame('bob', $response->username);
        $this->assertSame('bob@example.com', $response->email);
    }

    public function testSanitizesUsernameAndEmailButNotFullname(): void
    {
        // username_claim/email_claim strip <, >, ", ', %, ;, (, ), &, \ from
        // the resolved value; name_claim does not go through that filter.
        $claims = [
            'preferred_username' => "a'b\"c&d",
            'email' => "a'b\"c&d",
            'name' => "a'b\"c&d",
        ];

        $plugin = $this->createPlugin($claims, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame('abcd', $response->username);
        $this->assertSame('abcd', $response->email);
        $this->assertSame('a\'b"c&d', $response->fullname);
    }

    public function testFallsBackToUsernameWhenNameClaimIsUnresolvable(): void
    {
        $claims = ['preferred_username' => 'alice', 'email' => 'alice@example.com'];

        $plugin = $this->createPlugin($claims, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame('alice', $response->fullname);
    }

    public function testMissingUsernameClaimPreventsLoginWithoutAddingAResult(): void
    {
        $claims = ['email' => 'alice@example.com', 'name' => 'Alice Example'];

        $plugin = $this->createPlugin($claims, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertNotSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertSame([], $event->getArgument('result', []));
    }

    public function testMissingEmailClaimPreventsLoginWithoutAddingAResult(): void
    {
        $claims = ['preferred_username' => 'alice', 'name' => 'Alice Example'];

        $plugin = $this->createPlugin($claims, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertNotSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertSame([], $event->getArgument('result', []));
    }

    public function testUsesCustomConfiguredClaimNames(): void
    {
        $claims = ['sub' => 'x', 'upn' => 'carol', 'mail' => 'carol@example.com', 'display_name' => 'Carol Example'];
        $params = ['username_claim' => 'upn', 'email_claim' => 'mail', 'name_claim' => 'display_name'];

        $plugin = $this->createPlugin($claims, $params);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame('carol', $response->username);
        $this->assertSame('carol@example.com', $response->email);
        $this->assertSame('Carol Example', $response->fullname);
    }
}
