<?php

namespace Tests\Unit\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Component\Externallogin\Administrator\Helper\ExternalloginHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Traits\TestDatabaseTrait;

#[CoversClass(ExternalloginHelper::class)]
class ExternalloginHelperTest extends TestCase
{
    use TestDatabaseTrait;

    /**
     * Builds a database mock whose quote() wraps values in single quotes and
     * whose getQuery(true) returns $query — the setup shared by every test
     * that reaches ExternalloginHelper::getGroups()'s query-building path.
     */
    private function createQuotingDatabase(QueryInterface $query): DatabaseInterface
    {
        $database = $this->createMock(DatabaseInterface::class);
        $database->method('quote')->willReturnCallback(fn ($text) => "'" . $text . "'");
        $database->method('getQuery')->willReturn($query);

        return $database;
    }

    public function testReturnsEmptyArrayWhenPathIsEmpty(): void
    {
        // No database method should ever be called for a malformed path —
        // a bare stub with no expectations proves that.
        $this->bindDatabase($this->createStub(DatabaseInterface::class));

        $this->assertSame([], ExternalloginHelper::getGroups(''));
    }

    public function testReturnsEmptyArrayWhenPathEndsWithSeparator(): void
    {
        $this->bindDatabase($this->createStub(DatabaseInterface::class));

        $this->assertSame([], ExternalloginHelper::getGroups('Public/Registered/', '/'));
    }

    public function testReturnsEmptyArrayWhenAnIntermediateSegmentIsEmpty(): void
    {
        // "Public//Author": the empty segment is neither the last one nor the
        // root (index 0), which the method treats as an incorrect path. The
        // query for the last segment ("Author") is already built by the time
        // this is detected, but setQuery()/loadColumn() must never be reached.
        $query = $this->createMockQuery();

        $database = $this->createQuotingDatabase($query);
        $database->expects($this->never())->method('setQuery');
        $database->expects($this->never())->method('loadColumn');

        $this->bindDatabase($database);

        $this->assertSame([], ExternalloginHelper::getGroups('Public//Author', '/'));
    }

    public function testResolvesAHierarchicalGroupPath(): void
    {
        $joins = [];
        $whereClauses = [];

        $query = $this->createMockQuery();
        $query->method('where')->willReturnCallback(function ($condition) use ($query, &$whereClauses) {
            $whereClauses[] = $condition;

            return $query;
        });
        $query->method('leftJoin')->willReturnCallback(function ($join) use ($query, &$joins) {
            $joins[] = $join;

            return $query;
        });

        $database = $this->createQuotingDatabase($query);
        $database->expects($this->once())->method('setQuery')->with($query);
        $database->method('loadColumn')->willReturn([42]);

        $this->bindDatabase($database);

        $this->assertSame([42], ExternalloginHelper::getGroups('Public/Registered/Author', '/'));
        $this->assertSame(
            [
                '#__usergroups AS a1 ON a1.id = a2.parent_id',
                '#__usergroups AS a0 ON a0.id = a1.parent_id',
            ],
            $joins
        );
        $this->assertSame(
            ["a2.title = 'Author'", "a1.title LIKE 'Registered'", "a0.title LIKE 'Public'"],
            $whereClauses
        );
    }

    public function testAbsolutePathConstrainsTheRootGroupToHaveNoParent(): void
    {
        // A leading separator ("/Public/Registered") means the top-level
        // segment must be a root group (parent_id = 0) rather than matching
        // any group whose title happens to be empty.
        $whereClauses = [];

        $query = $this->createMockQuery();
        $query->expects($this->once())
            ->method('leftJoin')
            ->with('#__usergroups AS a1 ON a1.id = a2.parent_id')
            ->willReturnSelf();
        $query->method('where')->willReturnCallback(function ($condition) use ($query, &$whereClauses) {
            $whereClauses[] = $condition;

            return $query;
        });

        $database = $this->createQuotingDatabase($query);
        $database->method('loadColumn')->willReturn([1]);

        $this->bindDatabase($database);

        $this->assertSame([1], ExternalloginHelper::getGroups('/Public/Registered', '/'));
        $this->assertSame(
            ["a2.title = 'Registered'", "a1.title LIKE 'Public'", 'a1.parent_id = 0'],
            $whereClauses
        );
    }

    public function testEmptySeparatorTreatsTheWholePathAsASingleGroupName(): void
    {
        $query = $this->createMockQuery();
        $query->expects($this->once())
            ->method('where')
            ->with('a0.title = \'Public/Registered\'')
            ->willReturnSelf();

        $database = $this->createQuotingDatabase($query);
        $database->method('loadColumn')->willReturn([7]);

        $this->bindDatabase($database);

        $this->assertSame([7], ExternalloginHelper::getGroups('Public/Registered', ''));
    }
}
