<?php

namespace Tests\Unit\Plugins\Authentication\Externallogin;

use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Externallogin\Administrator\Authentication\ExternalAuthenticationResponse;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Plugin\Authentication\Externallogin\Extension\Externallogin;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Exercises Externallogin::onUserAuthenticate() against real Joomla event objects to prove that
 * the blocked/autoregister/autoupdate business logic acts on the true AuthenticationEvent subject
 * (the object Joomla core's CMSApplication::login()/Authentication::authorise() actually inspect),
 * not a locally-reassigned copy — and that it runs exactly once per login.
 *
 * Constructed via ReflectionClass::newInstanceWithoutConstructor() to skip CMSPlugin's
 * language-loading/log-registration constructor, matching CasloginTest's approach.
 */
#[CoversClass(Externallogin::class)]
class ExternalloginTest extends TestCase
{
    protected function tearDown(): void
    {
        // Factory::$container/$database are static and shared across tests.
        Factory::$container = null;
        Factory::$database = null;
    }

    private function createPlugin(): Externallogin
    {
        $reflection = new ReflectionClass(Externallogin::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Registers a fake dispatcher whose "onExternalLogin" listener mimics exactly what a
     * protocol plugin (e.g. Caslogin::onExternalLogin) does: build a new
     * ExternalAuthenticationResponse carrying username/email/fullname/server/groups, and sync
     * only status/type/username/email/fullname back onto the original response object when it
     * differs from the newly-built one.
     */
    private function bindDispatcherWithProtocolPlugin(object $server, string $username, string $email, array $groups = []): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->addListener('onExternalLogin', function (Event $event) use ($server, $username, $email, $groups) {
            $response = $event->getArgument('response');
            $extResponse = ExternalAuthenticationResponse::fromResponse($response);
            $extResponse->status = Authentication::STATUS_SUCCESS;
            $extResponse->server = $server;
            $extResponse->type = 'system.faketest';
            $extResponse->username = $username;
            $extResponse->email = $email;
            $extResponse->fullname = 'Test User';

            if (!empty($groups)) {
                $extResponse->groups = $groups;
            }

            if ($response !== $extResponse) {
                $response->status = $extResponse->status;
                $response->type = $extResponse->type;
                $response->username = $extResponse->username;
                $response->email = $extResponse->email;
                $response->fullname = $extResponse->fullname;
            }

            $event->setArgument('response', $extResponse);
            $results = $event->getArgument('result', []);
            $results[] = true;
            $event->setArgument('result', $results);
        });

        $container = new Container();
        $container->set(DispatcherInterface::class, fn () => $dispatcher, true);
        Factory::$container = $container;
    }

    private function bindDatabase(DatabaseInterface $database): void
    {
        $container = Factory::$container ?? new Container();
        $container->set(DatabaseInterface::class, fn () => $database, true);
        Factory::$container = $container;
    }

    private function createMockQuery(): QueryInterface
    {
        $query = $this->createMock(QueryInterface::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('delete')->willReturnSelf();
        $query->method('insert')->willReturnSelf();
        $query->method('columns')->willReturnSelf();
        $query->method('values')->willReturnSelf();
        $query->method('bind')->willReturnSelf();
        $query->method('setLimit')->willReturnSelf();

        return $query;
    }

    private function dispatch(Externallogin $plugin): AuthenticationEvent
    {
        $subject = new AuthenticationResponse();
        $event = new AuthenticationEvent('onUserAuthenticate', [
            'credentials' => ['username' => 'irrelevant'],
            'options' => [],
            'subject' => $subject,
        ]);
        $plugin->onUserAuthenticate($event);

        return $event;
    }

    public function testBlockedUserStatusPropagatesToTheTrueEventSubject(): void
    {
        $server = (object) ['id' => 1, 'params' => new Registry([
            'regex_user' => '(?!)',
            'regex_email' => '(?!)',
        ])];
        $this->bindDispatcherWithProtocolPlugin($server, 'alice', 'alice@example.com');

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getQuery')->willReturn($this->createMockQuery());
        $database->method('createQuery')->willReturn($this->createMockQuery());
        $database->method('loadResult')->willReturn(null);
        $this->bindDatabase($database);

        $plugin = $this->createPlugin();
        $event = $this->dispatch($plugin);

        // The object authenticate()/CMSApplication::login() actually inspects is
        // $event->getAuthenticationResponse() — not any locally-reassigned copy.
        $this->assertSame(
            Authentication::STATUS_DENIED,
            $event->getAuthenticationResponse()->status,
            'Blocked user must be denied on the true event subject, not just a disconnected local copy.'
        );
    }

    public function testSuccessfulExistingUserLoginDoesNotAddDynamicPropertiesToTheSubject(): void
    {
        $server = (object) ['id' => 1, 'params' => new Registry([
            'regex_user' => '.*',
            'regex_email' => '.*',
            'autoupdate' => 0,
        ])];
        $this->bindDispatcherWithProtocolPlugin($server, 'bob', 'bob@example.com');

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getQuery')->willReturn($this->createMockQuery());
        $database->method('createQuery')->willReturn($this->createMockQuery());
        $database->method('loadResult')->willReturn(42); // existing user id
        $this->bindDatabase($database);

        $plugin = $this->createPlugin();
        $event = $this->dispatch($plugin);
        $subject = $event->getAuthenticationResponse();

        $this->assertSame(Authentication::STATUS_SUCCESS, $subject->status);
        $this->assertSame('externallogin', $subject->type);
        $this->assertSame('bob', $subject->username);

        // Only base AuthenticationResponse properties may exist on the plain core object —
        // ExternalAuthenticationResponse-only properties (server/groups/subtype/message) must
        // never be written onto it via a blind get_object_vars() copy (PHP 8.2 dynamic property
        // deprecation, issue #231).
        $this->assertFalse(isset($subject->server), 'server must not be set as a dynamic property on the plain AuthenticationResponse subject.');
        $this->assertFalse(isset($subject->groups), 'groups must not be set as a dynamic property on the plain AuthenticationResponse subject.');
    }

    public function testAutoupdateWithGroupsRunsExactlyOnce(): void
    {
        $server = (object) ['id' => 1, 'params' => new Registry([
            'regex_user' => '.*',
            'regex_email' => '.*',
            'autoupdate' => 1,
        ])];
        $this->bindDispatcherWithProtocolPlugin($server, 'carol', 'carol@example.com', ['7']);

        $query = $this->createMockQuery();

        $existingUser = $this->createMock(User::class);
        $existingUser->method('load')->willReturn(true);
        $existingUser->email = 'carol@example.com'; // matches response -> no save() needed
        $existingUser->name = 'Test User';

        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory->method('loadUserById')->willReturn($existingUser);

        $database = $this->createMock(DatabaseInterface::class);
        $database->method('getQuery')->willReturn($query);
        $database->method('createQuery')->willReturn($query);
        $database->method('loadResult')->willReturn(42);
        // updateUser() issues exactly one DELETE + one INSERT against #__user_usergroup_map per
        // invocation. onUserAuthenticate() is the ONLY place this logic runs (onUserAuthorisation
        // was removed as dead code — see class docblock note), so a single onUserAuthenticate
        // dispatch must never produce more than this one DELETE+INSERT pair.
        $database->expects($this->exactly(2))->method('execute');

        $container = new Container();
        $container->set(DatabaseInterface::class, fn () => $database, true);
        $container->set(UserFactoryInterface::class, fn () => $userFactory, true);
        $dispatcher = Factory::$container->get(DispatcherInterface::class);
        $container->set(DispatcherInterface::class, fn () => $dispatcher, true);
        Factory::$container = $container;

        $plugin = $this->createPlugin();
        $event = $this->dispatch($plugin);

        $this->assertSame(Authentication::STATUS_SUCCESS, $event->getAuthenticationResponse()->status);
    }
}
