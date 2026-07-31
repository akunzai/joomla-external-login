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

use Joomla\CMS\Authentication\AuthenticationResponse;

/**
 * External Login Authentication Response class.
 * Extends Joomla core AuthenticationResponse with explicit properties
 * to avoid PHP 8.2+ dynamic property creation deprecation warnings.
 *
 * @since 5.2.0
 */
class ExternalAuthenticationResponse extends AuthenticationResponse
{
    /**
     * External authentication server configuration object.
     *
     * @since 5.2.0
     */
    public ?object $server = null;

    /**
     * Additional response message.
     *
     * @since 5.2.0
     */
    public string $message = '';

    /**
     * Group IDs or names assigned to the authenticated user.
     *
     * @since 5.2.0
     */
    public ?array $groups = null;

    /**
     * Subtype of external login plugin.
     *
     * @since 5.2.0
     */
    public string $subtype = '';

    /**
     * Create an ExternalAuthenticationResponse from a standard AuthenticationResponse.
     *
     * @since 5.2.0
     */
    public static function fromResponse(AuthenticationResponse $response): self
    {
        if ($response instanceof self) {
            return $response;
        }

        $new = new self();

        foreach (get_object_vars($response) as $property => $value) {
            $new->$property = $value;
        }

        return $new;
    }
}
