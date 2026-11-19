<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Session;

defined('_JEXEC') or die;

/**
 * Pending authorization requests for one server, keyed by their "state" value.
 *
 * Several can be in flight at once (multiple tabs, autologin then manual login). A callback
 * consumes only the transaction whose state it carries, so a stale or forged callback cannot
 * cancel the others, and each transaction is single-use, so a replayed callback is rejected.
 * The list is bounded in size and age.
 *
 * Stored as a JSON string in Joomla user state; pure functions so it can be unit tested.
 *
 * @since 5.2.1
 */
final class LoginTransactions
{
    public const MAX_TRANSACTIONS = 5;

    public const TTL_SECONDS = 900;

    /**
     * Add a transaction, dropping expired ones and the oldest beyond the limit.
     *
     * @param array<string, string> $data nonce, verifier and redirect_uri
     */
    public static function add(?string $stored, string $state, array $data, int $now): string
    {
        $transactions = self::prune(self::decode($stored), $now);
        unset($transactions[$state]);
        $transactions[$state] = $data + ['created' => $now];

        if (count($transactions) > self::MAX_TRANSACTIONS) {
            $transactions = array_slice($transactions, -self::MAX_TRANSACTIONS, null, true);
        }

        return self::encode($transactions);
    }

    /**
     * Remove and return the transaction for the given state.
     *
     * @return array{0: array<string, mixed>|null, 1: string} the transaction (null when none matches) and the remaining store
     */
    public static function consume(?string $stored, string $state, int $now): array
    {
        $transactions = self::prune(self::decode($stored), $now);
        $match = null;

        if ($state !== '') {
            foreach ($transactions as $key => $transaction) {
                if (hash_equals((string) $key, $state)) {
                    $match = $transaction;
                    unset($transactions[$key]);
                    break;
                }
            }
        }

        return [$match, self::encode($transactions)];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function decode(?string $stored): array
    {
        if ($stored === null || $stored === '') {
            return [];
        }

        $decoded = json_decode($stored, true);

        if (!is_array($decoded)) {
            return [];
        }

        $transactions = [];

        foreach ($decoded as $state => $transaction) {
            if (is_string($state) && is_array($transaction)) {
                $transactions[$state] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * @param array<string, array<string, mixed>> $transactions
     *
     * @return array<string, array<string, mixed>>
     */
    private static function prune(array $transactions, int $now): array
    {
        return array_filter(
            $transactions,
            static fn (array $transaction): bool => is_int($transaction['created'] ?? null)
                && $transaction['created'] <= $now
                && $now - $transaction['created'] <= self::TTL_SECONDS
        );
    }

    /**
     * @param array<string, array<string, mixed>> $transactions
     */
    private static function encode(array $transactions): string
    {
        return (string) json_encode((object) $transactions);
    }
}
