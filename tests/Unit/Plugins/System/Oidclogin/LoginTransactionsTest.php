<?php

namespace Tests\Unit\Plugins\System\Oidclogin;

use Joomla\Plugin\System\Oidclogin\Session\LoginTransactions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginTransactions::class)]
class LoginTransactionsTest extends TestCase
{
    private const NOW = 1_800_000_000;

    /**
     * @return array<string, string>
     */
    private function data(string $suffix): array
    {
        return ['nonce' => 'nonce-' . $suffix, 'verifier' => 'verifier-' . $suffix, 'redirect_uri' => 'https://www.dev.local/' . $suffix];
    }

    public function testConsumeReturnsTheMatchingTransactionOnce(): void
    {
        $stored = LoginTransactions::add(null, 'state-a', $this->data('a'), self::NOW);

        [$transaction, $stored] = LoginTransactions::consume($stored, 'state-a', self::NOW + 10);
        $this->assertSame('nonce-a', $transaction['nonce']);
        $this->assertSame('verifier-a', $transaction['verifier']);
        $this->assertSame('https://www.dev.local/a', $transaction['redirect_uri']);

        [$replayed] = LoginTransactions::consume($stored, 'state-a', self::NOW + 11);
        $this->assertNull($replayed, 'A consumed transaction must not be usable again');
    }

    public function testAnUnknownStateLeavesPendingTransactionsIntact(): void
    {
        $stored = LoginTransactions::add(null, 'state-a', $this->data('a'), self::NOW);
        $stored = LoginTransactions::add($stored, 'state-b', $this->data('b'), self::NOW);

        [$forged, $stored] = LoginTransactions::consume($stored, 'forged', self::NOW + 1);
        $this->assertNull($forged);

        [$empty, $stored] = LoginTransactions::consume($stored, '', self::NOW + 1);
        $this->assertNull($empty);

        [$a, $stored] = LoginTransactions::consume($stored, 'state-a', self::NOW + 2);
        [$b] = LoginTransactions::consume($stored, 'state-b', self::NOW + 2);
        $this->assertSame('nonce-a', $a['nonce']);
        $this->assertSame('nonce-b', $b['nonce']);
    }

    public function testExpiredTransactionsAreRejected(): void
    {
        $stored = LoginTransactions::add(null, 'state-a', $this->data('a'), self::NOW);

        [$transaction] = LoginTransactions::consume($stored, 'state-a', self::NOW + LoginTransactions::TTL_SECONDS + 1);
        $this->assertNull($transaction);

        [$transaction] = LoginTransactions::consume($stored, 'state-a', self::NOW + LoginTransactions::TTL_SECONDS);
        $this->assertNotNull($transaction);
    }

    public function testTransactionsFromTheFutureAreRejected(): void
    {
        $stored = LoginTransactions::add(null, 'state-a', $this->data('a'), self::NOW + 60);

        [$transaction] = LoginTransactions::consume($stored, 'state-a', self::NOW);
        $this->assertNull($transaction);
    }

    public function testKeepsOnlyTheNewestTransactions(): void
    {
        $stored = null;

        for ($i = 0; $i < LoginTransactions::MAX_TRANSACTIONS + 2; $i++) {
            $stored = LoginTransactions::add($stored, 'state-' . $i, $this->data((string) $i), self::NOW + $i);
        }

        $this->assertCount(LoginTransactions::MAX_TRANSACTIONS, json_decode($stored, true));

        [$oldest] = LoginTransactions::consume($stored, 'state-0', self::NOW + 10);
        [$newest] = LoginTransactions::consume($stored, 'state-' . (LoginTransactions::MAX_TRANSACTIONS + 1), self::NOW + 10);
        $this->assertNull($oldest);
        $this->assertNotNull($newest);
    }

    public function testIgnoresCorruptStorage(): void
    {
        [$transaction, $stored] = LoginTransactions::consume('not json', 'state-a', self::NOW);
        $this->assertNull($transaction);
        $this->assertSame('{}', $stored);

        [$transaction] = LoginTransactions::consume('{"state-a":"oops","state-b":{"nonce":"x"}}', 'state-b', self::NOW);
        $this->assertNull($transaction, 'Entries without a creation time are dropped');
    }
}
