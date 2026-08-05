<?php

/**
 * @author      Charley Wu <akunzai@gmail.com>
 * @copyright   Copyright (C) 2008-2026 Christophe Demko, Ioannis Barounis, Alexandre Gandois, Charley Wu. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\System\Oidclogin\Claims;

defined('_JEXEC') or die;

/**
 * Resolves a dot-path expression (e.g. "realm_access.roles") against a
 * decoded claims array, without requiring a JSONPath dependency.
 *
 * @since 5.2.0
 */
final class ClaimsResolver
{
    /**
     * Resolve a dot-path expression against a claims array.
     *
     * @param array<string, mixed> $claims The claims to resolve against
     * @param string $path The dot-path expression, e.g. "realm_access.roles"
     *
     * @return mixed The resolved value, or null when the path cannot be resolved
     */
    public static function resolve(array $claims, string $path)
    {
        if ($path === '') {
            return null;
        }

        $value = $claims;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
