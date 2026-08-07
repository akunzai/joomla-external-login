<?php

namespace Tests\Unit\Plugins\System\Caslogin;

use DOMDocument;
use DOMXPath;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\Component\Externallogin\Administrator\Authentication\ExternalAuthenticationResponse;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use Joomla\Plugin\System\Caslogin\Extension\Caslogin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\Unit\Traits\TestDatabaseTrait;

/**
 * Exercises Caslogin::onExternalLogin() — the XML/XPath attribute-extraction
 * logic that turns a parsed CAS response into an AuthenticationResponse.
 *
 * Constructed via ReflectionClass::newInstanceWithoutConstructor() to skip
 * CMSPlugin's language-loading/log-registration constructor, which needs a
 * full Joomla application bootstrap unrelated to what's under test here.
 */
#[CoversClass(Caslogin::class)]
class CasloginTest extends TestCase
{
    use TestDatabaseTrait;

    protected function setUp(): void
    {
        // The email_verified_xpath denial branch calls Text::_(...), which goes through
        // Factory::getLanguage(). Stub Factory::$language so unit tests never hit the
        // createLanguage() path (needs JPATH_CONFIGURATION / a full CMS bootstrap).
        Factory::$language = new class {
            public function _($string, $jsSafe = false, $interpretBackSlashes = true): string
            {
                return $string;
            }
        };
    }

    protected function tearDown(): void
    {
        // Keep TestDatabaseTrait's container reset — class tearDown replaces the trait method.
        Factory::$container = null;
        Factory::$language = null;
    }

    /**
     * Parses $xml exactly as Caslogin::onAfterInitialise() does, returning
     * the [DOMXPath, authenticationSuccess node] pair the plugin caches on
     * $this->xpath / $this->success before dispatching onExternalLogin.
     */
    private function parseCasResponse(string $xml): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cas', 'http://www.yale.edu/tp/cas');

        $success = $xpath->query('/cas:serviceResponse/cas:authenticationSuccess[1]')->item(0);

        return [$xpath, $success];
    }

    /**
     * Builds a Caslogin instance with $xpath/$success/$server pre-set via
     * reflection, ready to call onExternalLogin() on directly.
     */
    private function createPlugin(string $xml, array $params, int $serverId = 5): Caslogin
    {
        [$xpath, $success] = $this->parseCasResponse($xml);

        $reflection = new ReflectionClass(Caslogin::class);
        $plugin = $reflection->newInstanceWithoutConstructor();

        $server = (object) ['id' => $serverId, 'params' => new Registry($params)];

        foreach (['xpath' => $xpath, 'success' => $success, 'server' => $server] as $property => $value) {
            $reflectionProperty = $reflection->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($plugin, $value);
        }

        return $plugin;
    }

    private function dispatch(Caslogin $plugin, AuthenticationResponse $response): Event
    {
        $event = new Event('onExternalLogin', ['response' => $response]);
        $plugin->onExternalLogin($event);

        return $event;
    }

    private const BASE_XML = <<<'XML'
        <cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
            <cas:authenticationSuccess>
                <cas:user>alice</cas:user>
                <cas:attributes>
                    <cas:email>alice@example.com</cas:email>
                    <cas:display_name>Alice Example</cas:display_name>
                    <cas:group>Public/Editors</cas:group>
                </cas:attributes>
            </cas:authenticationSuccess>
        </cas:serviceResponse>
        XML;

    // Guards against XPath's "empty node-set = string" comparison, which is always false and
    // would otherwise misclassify an absent attribute as an explicit "false" (see the
    // email_verified_xpath cookbook in cas.xml's field description).
    private const EMAIL_VERIFIED_XPATH = "boolean(not(cas:attributes/cas:emailVerified) or cas:attributes/cas:emailVerified = 'true')";

    private const BASE_PARAMS = [
        'username_xpath' => 'string(cas:user)',
        'email_xpath' => 'string(cas:attributes/cas:email)',
        'name_xpath' => 'string(cas:attributes/cas:display_name)',
        'group_xpath' => '',
    ];

    public function testExtractsUsernameEmailAndFullnameFromTheCasResponse(): void
    {
        $plugin = $this->createPlugin(self::BASE_XML, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertSame('alice', $response->username);
        $this->assertSame('alice@example.com', $response->email);
        $this->assertSame('Alice Example', $response->fullname);
        $this->assertSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertSame('system.caslogin', $response->type);
        $this->assertContains(true, $event->getArgument('result', []));
    }

    public function testEmailVerifiedXpathUnsetLeavesLoginUnaffected(): void
    {
        $plugin = $this->createPlugin(self::BASE_XML, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertContains(true, $event->getArgument('result', []));
    }

    public function testEmailVerifiedXpathResolvingFalseDeniesLogin(): void
    {
        $xml = <<<'XML'
            <cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
                <cas:authenticationSuccess>
                    <cas:user>alice</cas:user>
                    <cas:attributes>
                        <cas:email>alice@example.com</cas:email>
                        <cas:display_name>Alice Example</cas:display_name>
                        <cas:emailVerified>false</cas:emailVerified>
                    </cas:attributes>
                </cas:authenticationSuccess>
            </cas:serviceResponse>
            XML;

        $params = [
            'email_verified_xpath' => self::EMAIL_VERIFIED_XPATH,
        ] + self::BASE_PARAMS;
        $plugin = $this->createPlugin($xml, $params);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        // Must claim the attempt (non-empty result) so authentication/externallogin stops
        // propagation and the core authentication/joomla plugin cannot overwrite the denial
        // with "Empty password not allowed." (#249-class bug on the email_verified deny path).
        $this->assertSame(Authentication::STATUS_DENIED, $response->status);
        $this->assertContains(true, $event->getArgument('result', []));
        $this->assertNotSame('', (string) $response->error_message);
        $this->assertStringNotContainsStringIgnoringCase('Empty password', (string) $response->error_message);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emailVerifiedNotDeniedProvider(): iterable
    {
        yield 'resolves true' => [
            <<<'XML'
                <cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
                    <cas:authenticationSuccess>
                        <cas:user>alice</cas:user>
                        <cas:attributes>
                            <cas:email>alice@example.com</cas:email>
                            <cas:display_name>Alice Example</cas:display_name>
                            <cas:emailVerified>true</cas:emailVerified>
                        </cas:attributes>
                    </cas:authenticationSuccess>
                </cas:serviceResponse>
                XML,
        ];
        yield 'attribute absent' => [self::BASE_XML];
    }

    #[DataProvider('emailVerifiedNotDeniedProvider')]
    public function testEmailVerifiedXpathResolvingTrueOrAbsentLeavesLoginUnaffected(string $xml): void
    {
        $params = [
            'email_verified_xpath' => self::EMAIL_VERIFIED_XPATH,
        ] + self::BASE_PARAMS;
        $plugin = $this->createPlugin($xml, $params);
        $response = new ExternalAuthenticationResponse();

        $event = $this->dispatch($plugin, $response);

        $this->assertSame(Authentication::STATUS_SUCCESS, $response->status);
        $this->assertContains(true, $event->getArgument('result', []));
    }

    public function testSanitizesUsernameAndEmailButNotFullname(): void
    {
        // username_xpath/email_xpath strip <, >, ", ', %, ;, (, ), &, \ from
        // the extracted value; name_xpath does not go through that filter.
        $xml = <<<'XML'
            <cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
                <cas:authenticationSuccess>
                    <cas:user>a'b"c&amp;d</cas:user>
                    <cas:attributes>
                        <cas:email>a'b"c&amp;d</cas:email>
                        <cas:display_name>a'b"c&amp;d</cas:display_name>
                    </cas:attributes>
                </cas:authenticationSuccess>
            </cas:serviceResponse>
            XML;

        $plugin = $this->createPlugin($xml, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame('abcd', $response->username);
        $this->assertSame('abcd', $response->email);
        $this->assertSame('a\'b"c&d', $response->fullname);
    }

    public function testGroupXpathEmptyLeavesGroupsUnset(): void
    {
        $plugin = $this->createPlugin(self::BASE_XML, self::BASE_PARAMS);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertFalse(isset($response->groups));
    }

    public function testGroupXpathWithNoMatchingNodesLeavesGroupsUnset(): void
    {
        $params = ['group_xpath' => 'cas:attributes/cas:role'] + self::BASE_PARAMS;
        $plugin = $this->createPlugin(self::BASE_XML, $params);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertFalse(isset($response->groups));
    }

    public function testStringGroupIsResolvedViaExternalloginHelperGetGroups(): void
    {
        // "Public/Editors" with the default '/' separator must reach the
        // database exactly like ExternalloginHelper::getGroups() expects.
        $query = $this->createMockQuery();
        $query->method('leftJoin')->willReturnSelf();

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('quote')->willReturnCallback(fn ($text) => "'" . $text . "'");
        $database->method('getQuery')->willReturn($query);
        $database->method('loadColumn')->willReturn([5]);

        $this->bindDatabase($database);

        $params = [
            'group_xpath' => 'cas:attributes/cas:group',
            'group_separator' => '/',
        ] + self::BASE_PARAMS;
        $plugin = $this->createPlugin(self::BASE_XML, $params);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame([5], $response->groups);
    }

    /**
     * @return iterable<string, array{string, int|null, array<string>}>
     */
    public static function numericGroupProvider(): iterable
    {
        yield 'existing group' => ['7', 7, ['7']];
        yield 'missing group' => ['999', null, []];
    }

    #[DataProvider('numericGroupProvider')]
    public function testNumericGroupWhenGroupIntegerEnabled(string $group, ?int $databaseResult, array $expectedGroups): void
    {
        $query = $this->createMockQuery();

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getQuery')->willReturn($query);
        $database->method('loadResult')->willReturn($databaseResult);

        $this->bindDatabase($database);

        $params = ['group_xpath' => 'cas:attributes/cas:group', 'group_integer' => 1] + self::BASE_PARAMS;
        $plugin = $this->createPlugin(str_replace('Public/Editors', $group, self::BASE_XML), $params);
        $response = new ExternalAuthenticationResponse();

        $this->dispatch($plugin, $response);

        $this->assertSame($expectedGroups, $response->groups);
    }
}
