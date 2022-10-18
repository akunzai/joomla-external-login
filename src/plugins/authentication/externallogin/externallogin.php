<?php

/**
 * @package     External_Login
 * @subpackage  External Login Plugin
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

// No direct access to this file
defined('_JEXEC') or die;

if (version_compare(JVERSION, '3.8.0', '>=')) {
    JLoader::registerAlias('ExternalloginLogger', '\\Joomla\\CMS\\Log\\Logger\\ExternalloginLogger');
}

JLoader::register('ExternalloginLogger', JPATH_ADMINISTRATOR . '/components/com_externallogin/log/logger.php');
JLoader::register('ExternalloginLogEntry', JPATH_ADMINISTRATOR . '/components/com_externallogin/log/entry.php');

/**
 * External Login - External Login plugin.
 *
 * @package     External_Login
 * @subpackage  External Login Plugin
 *
 * @since       2.0.0
 */
class PlgAuthenticationExternallogin extends JPlugin
{
    /**
     * Constructor.
     *
     * @param   object  $subject  The object to observe
     * @param   array   $config   An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
        JLog::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'authentication-externallogin'],
            JLog::ALL,
            ['authentication-externallogin-autoregister', 'authentication-externallogin-autoupdate', 'authentication-externallogin-blocked']
        );
    }

    /**
     * This method should handle any authorisation and report back to the subject
     *
     * @param   JAuthenticationResponse  $response  Authentication response object
     * @param   array            $options   Array of extra options
     *
     * @return  JAuthenticationResponse  The response
     *
     * @since   3.1.1.0
     */
    public function onUserAuthorisation($response, $options)
    {
        if ($response->type != 'externallogin') {
            return $response;
        }

        // Clone the response
        $response = clone $response;
        /** @var JRegistry */
        $params = $response->server->params;
        $userId = intval(JUserHelper::getUserId($response->username));
        $isUserNotFound = $userId === 0;
        $isUserBlocked = $this->isUserBlocked($params, $response->username, $response->email);

        if ($isUserBlocked) {
            if (boolval($params->get('log_blocked', 0))) {
                JLog::add(
                    new ExternalloginLogEntry(
                        'User "' . $response->username . '" is trying to ' . $isUserNotFound ? 'register' : 'login' . ' while he is blocked',
                        JLog::ERROR,
                        'authentication-externallogin-blocked'
                    )
                );
            }
            return $this->userLoginFail($response, $params->get('blocked_redirect_menuitem'), JAuthentication::STATUS_DENIED);
        }

        if ($isUserNotFound) {
            if (boolval($params->get('autoregister', 0))) {
                return $this->createNewUser($response);
            }
            if (boolval($params->get('log_autoregister', 0))) {
                JLog::add(
                    new ExternalloginLogEntry(
                        'User "' . $response->username . '" is trying to register while auto-register is disabled',
                        JLog::WARNING,
                        'authentication-externallogin-autoregister'
                    )
                );
            }
            return $this->userLoginFail($response, $params->get('unknown_redirect_menuitem'));
        }

        if (boolval($params->get('autoupdate', 0))) {
            return $this->updateUser($response, $userId);
        }

        return $response;
    }

    /**
     * This method should handle any authentication and report back to the subject
     *
     * @param   array            $credentials  Array holding the user credentials
     * @param   array            $options      Array of extra options
     * @param   JAuthenticationResponse  $response     Authentication response object
     *
     * @return	boolean
     */
    public function onUserAuthenticate($credentials, $options, &$response)
    {
        $results = JFactory::getApplication()->triggerEvent('onExternalLogin', [&$response]);

        if (count($results) === 0) {
            return false;
        }

        $response->subtype = $response->type;
        $response->type = 'externallogin';
        return true;
    }

    /**
     * @param JAuthenticationResponse $response
     * @return JAuthenticationResponse
     */
    private function createNewUser($response)
    {
        /** @var JRegistry */
        $params = $response->server->params;
        $isLogAutoRegister = boolval($params->get('log_autoregister', 0));
        $db = JFactory::getDbo();
        $user = JUser::getInstance();
        $user->set('id', 0);
        $user->set('name', $response->fullname);
        $user->set('username', $response->username);
        $user->set('email', $response->email);
        $user->set('usertype', 'deprecated');

        if (!$user->save()) {
            if ($isLogAutoRegister) {
                JLog::add(
                    new ExternalloginLogEntry(
                        $user->get('error'),
                        JLog::ERROR,
                        'authentication-externallogin-autoregister'
                    )
                );
            }
            return $this->userLoginFail($response, $params->get('incorrect_redirect_menuitem'));
        }

        JAccess::clearStatics();
        $this->addLoginRecord($response, intval($user->id));

        if ($isLogAutoRegister) {
            JLog::add(
                new ExternalloginLogEntry(
                    'Auto-register of user "'
                        . $user->username
                        . '" with fullname "'
                        . $response->fullname
                        . '" and email "'
                        . $response->email
                        . '" on server '
                        . $response->server->id,
                    JLog::INFO,
                    'authentication-externallogin-autoregister'
                )
            );
        }

        jimport('joomla.application.component.helper');
        $config    = JComponentHelper::getParams('com_users');
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
            $message = empty($response->groups)
                ? 'Auto-register default group "' . $defaultUserGroup . '" for user "' . $user->username . '" on server ' . $response->server->id
                : 'Auto-register new groups for user "' . $user->username . '" with groups (' . implode(',', $groups) . ') on server ' . $response->server->id;
            JLog::add(
                new ExternalloginLogEntry(
                    $message,
                    JLog::INFO,
                    'authentication-externallogin-autoregister'
                )
            );
        }

        return $response;
    }

    /**
     * @param JAuthenticationResponse $response
     * @param int $userId
     * @return JAuthenticationResponse
     */
    private function updateUser($response, $userId)
    {
        /** @var JRegistry */
        $params = $response->server->params;

        $isLogAutoUpdate = boolval($params->get('log_autoupdate', 0));
        $isNeedsUpdate = false;
        $db = JFactory::getDbo();
        $user = JUser::getInstance();

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
                JLog::add(
                    new ExternalloginLogEntry(
                        'Auto-update new groups of user "' . $user->username .
                            '" with groups (' . implode(',', $response->groups) . ') on server ' .
                            $response->server->id,
                        JLog::INFO,
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
            JLog::add(
                new ExternalloginLogEntry(
                    'Auto-update of user "'
                        . $user->username
                        . '" with fullname "'
                        . $response->fullname
                        . '" and email "'
                        . $response->email
                        . '" on server '
                        . $response->server->id,
                    JLog::INFO,
                    'authentication-externallogin-autoupdate'
                )
            );
        }
        JAccess::clearStatics();
        $this->addLoginRecord($response, $userId, true);
        return $response;
    }

    /**
     *
     * @param JRegistry $params
     * @param string $username
     * @param string $email
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
     *
     * @param JAuthenticationResponse $response
     * @param string|null $redirection
     * @param int $status
     * @return JAuthenticationResponse
     */
    private function userLoginFail(
        $response,
        $redirection = null,
        $status = JAuthentication::STATUS_DENIED | JAuthentication::STATUS_UNKNOWN,
    ) {
        if (!empty($redirection)) {
            JFactory::getApplication()->setUserState('com_externallogin.redirect', $redirection);
        }
        $response->status = $status;
        return $response;
    }

    /**
     * @param JAuthenticationResponse $response
     * @param int $userId
     * @param bool $isSkipExisting
     * @return void
     */
    private function addLoginRecord($response, $userId, $isSkipExisting = false)
    {
        $db = JFactory::getDbo();
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
        $query->insert(
            '#__externallogin_users'
        )->columns(
            'server_id, user_id'
        )->values(
            intval($response->server->id) . ',' . $userId
        );
        $db->setQuery($query);
        $db->execute();
    }
}
