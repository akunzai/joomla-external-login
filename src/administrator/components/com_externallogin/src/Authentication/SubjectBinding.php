<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Component\Externallogin\Administrator\Authentication;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Matching rules for binding an account to the provider's verified issuer + subject
 * instead of the username claim (ADR 0002). Database access stays with the caller, so the
 * rules can be tested on their own.
 *
 * @since 5.3.0
 */
final class SubjectBinding
{
    public const MODE_SUBJECT = 'subject';

    public const MODE_USERNAME = 'username';

    /**
     * Longest issuer or subject the binding columns hold. Longer values are refused, never
     * truncated, since a truncated identifier could match a different identity.
     */
    public const MAX_LENGTH = 255;

    /**
     * Whether a server binds accounts by subject. A missing value reads as username mode, so a
     * server saved before 5.3.0 keeps its behaviour even if the schema update did not reach it.
     */
    public static function isSubjectMode(Registry $params): bool
    {
        return $params->get('binding_mode', self::MODE_USERNAME) === self::MODE_SUBJECT;
    }

    /**
     * Whether an issuer/subject pair fits the binding columns.
     */
    public static function fits(string $issuer, string $subject): bool
    {
        return $issuer !== ''
            && $subject !== ''
            && strlen($issuer) <= self::MAX_LENGTH
            && strlen($subject) <= self::MAX_LENGTH;
    }

    /**
     * Decides which account a subject-mode login reaches.
     *
     * @param int $serverId Server the login came through
     * @param int|null $subjectUserId Account bound to this server with this issuer + subject, if any
     * @param int $usernameUserId Account whose username equals the username claim, or 0
     * @param array{server_id: int, issuer: ?string, subject: ?string}|null $usernameBinding Binding row of $usernameUserId, if any
     */
    public static function resolve(int $serverId, ?int $subjectUserId, int $usernameUserId, ?array $usernameBinding): SubjectMatch
    {
        if ($subjectUserId !== null) {
            return new SubjectMatch(SubjectMatchOutcome::Bound, $subjectUserId);
        }

        // No account carries this subject. An account with the claimed username can take it
        // over only once, when it is bound to this server and has no subject recorded yet.
        if ($usernameUserId === 0 || $usernameBinding === null || $usernameBinding['server_id'] !== $serverId) {
            return new SubjectMatch(SubjectMatchOutcome::None, $usernameUserId);
        }

        if ($usernameBinding['subject'] === null) {
            return new SubjectMatch(SubjectMatchOutcome::FirstUse, $usernameUserId);
        }

        // The account already belongs to a different subject, or to the same subject under an
        // issuer that has since changed. Either way it is not this identity's to claim.
        return new SubjectMatch(SubjectMatchOutcome::Mismatch, $usernameUserId);
    }
}
