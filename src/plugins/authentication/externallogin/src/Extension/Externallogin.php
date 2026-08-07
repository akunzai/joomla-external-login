<?php

/**
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @link        https://github.com/akunzai/joomla-external-login
 */

namespace Joomla\Plugin\Authentication\Externallogin\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Authentication\Authentication;
use Joomla\CMS\Authentication\AuthenticationResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Component\Externallogin\Administrator\Authentication\ExternalAuthenticationResponse;
use Joomla\Component\Externallogin\Administrator\Service\Logger\ExternalloginLogEntry;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Registry\Registry;

/**
 * External Login - External Login plugin.
 *
 * @since       2.0.0
 */
class Externallogin extends CMSPlugin
{
    /**
     * Constructor.
     *
     * @param DispatcherInterface $dispatcher The event dispatcher
     * @param array $config An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
        $this->loadLanguage();
        require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/src/Service/Logger/ExternalloginLogger.php';
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'authentication-externallogin'],
            Log::ALL,
            [
                'authentication-externallogin-autoregister',
                'authentication-externallogin-autoupdate',
                'authentication-externallogin-blocked',
                'authentication-externallogin-activation',
            ]
        );
    }

    /**
     * This method should handle any authentication and report back to the subject.
     *
     * @param AuthenticationEvent $event Authentication event
     *
     * @since 5.0.0
     */
    public function onUserAuthenticate(AuthenticationEvent $event): void
    {
        $response = $event->getAuthenticationResponse();
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        $externalEvent = new Event('onExternalLogin', ['response' => &$response]);
        $dispatcher->dispatch('onExternalLogin', $externalEvent);

        // Get the modified response back from the event
        $response = $externalEvent->getArgument('response');
        $results = $externalEvent->getArgument('result', []);

        if (count($results) === 0) {
            return;
        }

        $response = ExternalAuthenticationResponse::fromResponse($response);
        $response->subtype = $response->type;
        $response->type = 'externallogin';

        // Blocked/auto-register/auto-update checks run here rather than in onUserAuthorisation,
        // deliberately. By this point $response is an ExternalAuthenticationResponse carrying the
        // protocol plugin's server/groups data; the AuthenticationEvent's own subject
        // ($event->getAuthenticationResponse()) is always a plain core AuthenticationResponse
        // that never gains those properties (only the whitelisted sync below), so onUserAuthorisation
        // would never have access to $response->server/groups even if it were dispatched.
        if ($response->status === Authentication::STATUS_SUCCESS) {
            /** @var Registry */
            $params = $response->server->params;
            $userId = intval(UserHelper::getUserId($response->username));
            $isUserNotFound = $userId === 0;
            $isUserBlocked = $this->isUserBlocked($params, $response->username, $response->email);

            if ($isUserBlocked) {
                if (boolval($params->get('log_blocked', 0))) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'User "' . $response->username . '" is trying to ' . ($isUserNotFound ? 'register' : 'login') . ' while the user is blocked',
                            Log::ERROR,
                            'authentication-externallogin-blocked'
                        )
                    );
                }
                $this->userLoginFail($response, $params->get('blocked_redirect_menuitem'), Authentication::STATUS_DENIED);
            } elseif ($isUserNotFound) {
                if (boolval($params->get('autoregister', 0))) {
                    $this->createNewUser($response);
                } else {
                    if (boolval($params->get('log_autoregister', 0))) {
                        Log::add(
                            new ExternalloginLogEntry(
                                'User "' . $response->username . '" is trying to register while auto-register is disabled',
                                Log::WARNING,
                                'authentication-externallogin-autoregister'
                            )
                        );
                    }
                    $this->userLoginFail($response, $params->get('unknown_redirect_menuitem'));
                }
            } elseif (!$this->isActivatedForServer($userId, intval($response->server->id))) {
                if (boolval($params->get('log_not_activated', 0))) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'User "' . $response->username . '" is trying to login on server ' . $response->server->id
                                . ' but is not activated for this server',
                            Log::WARNING,
                            'authentication-externallogin-activation'
                        )
                    );
                }
                Factory::getApplication()->enqueueMessage(
                    Text::_('PLG_AUTHENTICATION_EXTERNALLOGIN_NOT_ACTIVATED'),
                    'error'
                );
                $this->userLoginFail($response, $params->get('not_activated_redirect_menuitem'), Authentication::STATUS_DENIED);
            } elseif (boolval($params->get('autoupdate', 0))) {
                $this->updateUser($response, $userId);
            }
        }

        // Sync modified response properties back to the original event subject — the object
        // Joomla core (CMSApplication::login()) actually inspects the status of, and that
        // authorise() later passes into onUserAuthorisation. Only copy properties the base
        // AuthenticationResponse class itself declares: it never gains ExternalAuthenticationResponse
        // -only properties like server/groups this way, which would otherwise reintroduce the
        // PHP 8.2 dynamic property deprecation on a plain core object (see issue #231).
        $origResponse = $event->getAuthenticationResponse();
        foreach (get_object_vars($response) as $property => $value) {
            if (property_exists(AuthenticationResponse::class, $property)) {
                $origResponse->$property = $value;
            }
        }

        // A protocol plugin (caslogin/oidclogin) already made a definitive decision on this
        // attempt — success, a bridge-side denial (blocked/unknown-user/not-activated), or a
        // protocol-side denial (e.g. email_verified / email_verified_xpath resolving false).
        // Stop propagation regardless of outcome: CAS/OIDC logins never populate
        // username/password, so leaving the event open lets a later core plugin (e.g.
        // authentication/joomla) treat the empty credentials as its own failure and overwrite
        // this response's status/error_message with a generic, misleading one (issue #249).
        $event->stopPropagation();
    }

    /**
     * @param ExternalAuthenticationResponse $response
     *
     * @return ExternalAuthenticationResponse
     */
    private function createNewUser($response)
    {
        /** @var Registry $params */
        $params = $response->server->params;
        $isLogAutoRegister = boolval($params->get('log_autoregister', 0));
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
        $user = $userFactory->loadUserById(0);
        $user->set('id', 0);
        $user->set('name', $response->fullname);
        $user->set('username', $response->username);
        $user->set('email', $response->email);
        $user->set('usertype', 'deprecated');

        if (!$user->save()) {
            if ($isLogAutoRegister) {
                $serverId = $response->server->id;
                Log::add(
                    new ExternalloginLogEntry(
                        $user->getError(),
                        Log::ERROR,
                        'authentication-externallogin-autoregister'
                    )
                );
            }
            return $this->userLoginFail($response, $params->get('incorrect_redirect_menuitem'));
        }

        Access::clearStatics();
        $this->addLoginRecord($response, intval($user->id));

        if ($isLogAutoRegister) {
            Log::add(
                new ExternalloginLogEntry(
                    'Auto-register of user "'
                        . $user->username
                        . '" with fullname "'
                        . $response->fullname
                        . '" and email "'
                        . $response->email
                        . '" on server '
                        . $response->server->id,
                    Log::INFO,
                    'authentication-externallogin-autoregister'
                )
            );
        }

        $config    = ComponentHelper::getParams('com_users');
        $defaultUserGroup = $params->get('usergroup', $config->get('new_usertype', 2));

        // Add the new groups
        $groups = empty($response->groups) ? [$defaultUserGroup] : $response->groups;
        $query = $db->getQuery(true);
        $query->insert('#__user_usergroup_map')->columns('user_id, group_id');

        foreach ($groups as $group) {
            $query->values(intval($user->id) . ',' . intval($group));
        }

        $db->setQuery($query);
        $db->execute();

        if ($isLogAutoRegister) {
            $serverId = $response->server->id;
            $message = empty($response->groups)
                ? 'Auto-register default group "' . $defaultUserGroup . '" for user "' . $user->username . '" on server ' . $serverId
                : 'Auto-register new groups for user "' . $user->username . '" with groups (' . implode(',', $groups) . ') on server ' . $serverId;
            Log::add(
                new ExternalloginLogEntry(
                    $message,
                    Log::INFO,
                    'authentication-externallogin-autoregister'
                )
            );
        }

        return $response;
    }

    /**
     * @param ExternalAuthenticationResponse $response
     * @param int $userId
     *
     * @return ExternalAuthenticationResponse
     */
    private function updateUser($response, $userId)
    {
        /** @var Registry */
        $params = $response->server->params;

        $isLogAutoUpdate = boolval($params->get('log_autoupdate', 0));
        $isNeedsUpdate = false;
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
        $user = $userFactory->loadUserById(0);

        $user->load($userId);
        if ($user->email != $response->email) {
            $user->email = $response->email;
            $isNeedsUpdate = true;
        }
        if ($user->name != $response->fullname) {
            $user->name = $response->fullname;
            $isNeedsUpdate = true;
        }

        // Needs to update groups?
        if (!empty($response->groups)) {
            // Delete the old groups
            $query = $db->getQuery(true);
            $query->delete('#__user_usergroup_map')->where('user_id = ' . $userId);
            $db->setQuery($query);
            $db->execute();

            // Add the groups
            $query = $db->getQuery(true);
            $query->insert('#__user_usergroup_map')->columns('user_id, group_id');

            foreach ($response->groups as $group) {
                $query->values($userId . ',' . intval($group));
            }

            $db->setQuery($query);
            $db->execute();

            if ($isLogAutoUpdate) {
                $serverId = $response->server->id;
                $groups = $response->groups;
                Log::add(
                    new ExternalloginLogEntry(
                        'Auto-update new groups of user "' . $user->username .
                            '" with groups (' . implode(',', $groups) . ') on server ' .
                            $serverId,
                        Log::INFO,
                        'authentication-externallogin-autoupdate'
                    )
                );
            }
        }

        if (!$isNeedsUpdate) {
            return $response;
        }

        // Attempt to update the user
        if ($user->save() && $isLogAutoUpdate) {
            $serverId = $response->server->id;
            Log::add(
                new ExternalloginLogEntry(
                    'Auto-update of user "'
                        . $user->username
                        . '" with fullname "'
                        . $response->fullname
                        . '" and email "'
                        . $response->email
                        . '" on server '
                        . $serverId,
                    Log::INFO,
                    'authentication-externallogin-autoupdate'
                )
            );
        }
        Access::clearStatics();
        return $response;
    }

    /**
     * Whether an existing Joomla user is bound (`#__externallogin_users`) to the given server.
     * A user with no binding row at all (e.g. a native Joomla account never activated for
     * external login) is treated the same as one bound to a different server — false either way.
     *
     * @param int $userId
     * @param int $serverId
     *
     * @return bool
     */
    private function isActivatedForServer($userId, $serverId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('server_id')->from('#__externallogin_users')->where('user_id = ' . $userId);
        $db->setQuery($query);
        $boundServerId = $db->loadResult();

        return $boundServerId !== null && intval($boundServerId) === $serverId;
    }

    /**
     * @param Registry $params
     * @param string $username
     * @param string $email
     *
     * @return bool
     */
    private function isUserBlocked($params, $username, $email)
    {
        $validUsernamePattern = $params->get('regex_user');
        $validEmailPattern = $params->get('regex_email');
        $isValidUsername = preg_match(chr(1) . $validUsernamePattern . chr(1), $username);
        $isValidEmail = preg_match(chr(1) . $validEmailPattern . chr(1), $email);
        return !($isValidUsername && $isValidEmail);
    }

    /**
     * @param ExternalAuthenticationResponse $response
     * @param string|null $redirection
     * @param int $status
     *
     * @return ExternalAuthenticationResponse
     */
    private function userLoginFail(
        $response,
        $redirection = null,
        $status = Authentication::STATUS_DENIED | Authentication::STATUS_UNKNOWN
    ) {
        if (!empty($redirection)) {
            /** @var CMSApplication */
            $app = Factory::getApplication();
            $app->setUserState('com_externallogin.redirect', $redirection);
        }
        /** @phpstan-ignore assign.propertyType */
        $response->status = $status;
        return $response;
    }

    /**
     * @param ExternalAuthenticationResponse $response
     * @param int $userId
     */
    private function addLoginRecord($response, $userId)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $serverId = intval($response->server->id);
        $query->insert(
            '#__externallogin_users'
        )->columns(
            'server_id, user_id'
        )->values(
            $serverId . ',' . $userId
        );
        $db->setQuery($query);
        $db->execute();
    }
}
