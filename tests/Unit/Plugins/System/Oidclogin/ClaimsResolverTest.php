<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use Joomla\Plugin\System\Oidclogin\Claims\ClaimsResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClaimsResolver::class)]
class ClaimsResolverTest extends TestCase
{
    public function testResolvesATopLevelClaim(): void
    {
        $this->assertSame('alice', ClaimsResolver::resolve(['preferred_username' => 'alice'], 'preferred_username'));
    }

    public function testResolvesANestedDotPathClaim(): void
    {
        $claims = ['realm_access' => ['roles' => ['editor', 'publisher']]];

        $this->assertSame(['editor', 'publisher'], ClaimsResolver::resolve($claims, 'realm_access.roles'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function unresolvablePathProvider(): iterable
    {
        yield 'missing top-level key' => [['email' => 'alice@example.com'], 'preferred_username'];
        yield 'missing nested key' => [['realm_access' => ['roles' => []]], 'realm_access.groups'];
        yield 'path continues past a scalar' => [['name' => 'Alice'], 'name.first'];
        yield 'empty path' => [['name' => 'Alice'], ''];
    }

    #[DataProvider('unresolvablePathProvider')]
    public function testReturnsNullWhenPathCannotBeResolved(array $claims, string $path): void
    {
        $this->assertNull(ClaimsResolver::resolve($claims, $path));
    }
}
