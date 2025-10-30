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
use Joomla\CMS\Event\User\AuthorisationEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
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
     * @param array $config An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->loadLanguage();
        require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/src/Service/Logger/ExternalloginLogger.php';
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'authentication-externallogin'],
            Log::ALL,
            ['authentication-externallogin-autoregister', 'authentication-externallogin-autoupdate', 'authentication-externallogin-blocked']
        );
    }

    /**
     * This method should handle any authorisation and report back to the subject.
     *
     * @param AuthorisationEvent $event Authorisation event
     *
     * @since   5.0.0
     */
    public function onUserAuthorisation(AuthorisationEvent $event): void
    {
        $response = $event->getAuthenticationResponse();
        $options = $event->getOptions();

        if ($response->type != 'externallogin') {
            return;
        }

        // Clone the response
        $response = clone $response;
        /** @var Registry */
        /** @phpstan-ignore-next-line */
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
            $response = $this->userLoginFail($response, $params->get('blocked_redirect_menuitem'), Authentication::STATUS_DENIED);
            $event->addResult($response);
            return;
        }

        if ($isUserNotFound) {
            if (boolval($params->get('autoregister', 0))) {
                $response = $this->createNewUser($response);
                $event->addResult($response);
                return;
            }
            if (boolval($params->get('log_autoregister', 0))) {
                Log::add(
                    new ExternalloginLogEntry(
                        'User "' . $response->username . '" is trying to register while auto-register is disabled',
                        Log::WARNING,
                        'authentication-externallogin-autoregister'
                    )
                );
            }
            $response = $this->userLoginFail($response, $params->get('unknown_redirect_menuitem'));
            $event->addResult($response);
            return;
        }

        if (boolval($params->get('autoupdate', 0))) {
            $response = $this->updateUser($response, $userId);
            $event->addResult($response);
            return;
        }

        $event->addResult($response);
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

        $response->subtype = $response->type;
        $response->type = 'externallogin';

        // Stop event propagation to prevent other authentication plugins from running
        if ($response->status === Authentication::STATUS_SUCCESS) {
            $event->stopPropagation();
        }
    }

    /**
     * @param AuthenticationResponse $response
     *
     * @return AuthenticationResponse
     */
    private function createNewUser($response)
    {
        /** @var Registry $params */
        $params = $response->server->params; // @phpstan-ignore property.notFound
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
                /** @phpstan-ignore-next-line */
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
                        /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
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
     * @param AuthenticationResponse $response
     * @param int $userId
     *
     * @return AuthenticationResponse
     */
    private function updateUser($response, $userId)
    {
        /** @var Registry */
        /** @phpstan-ignore-next-line */
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
                /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
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
        $this->addLoginRecord($response, $userId, true);
        return $response;
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
     * @param AuthenticationResponse $response
     * @param string|null $redirection
     * @param int $status
     *
     * @return AuthenticationResponse
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
     * @param AuthenticationResponse $response
     * @param int $userId
     * @param bool $isSkipExisting
     */
    private function addLoginRecord($response, $userId, $isSkipExisting = false)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if ($isSkipExisting) {
            $query = $db->getQuery(true);
            $query->select('*')->from('#__externallogin_users')->where('user_id = ' . $userId);
            $db->setQuery($query);
            $results = $db->loadObject();
            if (!empty($results)) {
                return;
            }
        }

        $query = $db->getQuery(true);
        /** @phpstan-ignore-next-line */
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
