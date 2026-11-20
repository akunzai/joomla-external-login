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
 * Result of SubjectBinding::resolve().
 *
 * @since 5.3.0
 */
final class SubjectMatch
{
    /**
     * @param SubjectMatchOutcome $outcome What the login should do
     * @param int $userId The account the outcome refers to, or 0 for none
     */
    public function __construct(
        public readonly SubjectMatchOutcome $outcome,
        public readonly int $userId
    ) {
    }
}
