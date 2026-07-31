<?php

namespace Tests\Unit\Traits;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\MockObject\MockObject;

trait TestDatabaseTrait
{
    protected function tearDown(): void
    {
        // Factory::$container is static, shared across tests — reset it so
        // one test's stub database never leaks into the next.
        Factory::$container = null;
    }

    /**
     * Registers $database as the resolved Joomla\Database\DatabaseInterface service.
     */
    private function bindDatabase(DatabaseInterface $database): void
    {
        $container = new Container();
        $container->set(DatabaseInterface::class, fn () => $database, true);
        Factory::$container = $container;
    }

    /**
     * Builds a mock QueryInterface with select(), from(), where() chained to return self.
     */
    private function createMockQuery(): QueryInterface&MockObject
    {
        $query = $this->createMock(QueryInterface::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();

        return $query;
    }
}
