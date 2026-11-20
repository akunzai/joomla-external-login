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

/**
 * @since 5.3.0
 */
enum SubjectMatchOutcome
{
    /** An account is bound to this issuer + subject: log in to it, whatever the username claim says. */
    case Bound;

    /** The claimed account is bound to this server without a subject: record this one on it (trust on first use). */
    case FirstUse;

    /** The claimed account is bound to this server under another subject or issuer: deny. */
    case Mismatch;

    /** No account to reach by subject: the existing rules (auto-register, not activated) apply. */
    case None;
}
