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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

// No direct access to this file
defined('_JEXEC') or die;

// Load component classes via autoloading
require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/log/logger.php';
require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/log/entry.php';
/**
 * External Login - External Login plugin.
 *
 * @since       2.0.0
 */
class PlgSystemExternallogin extends Joomla\CMS\Plugin\CMSPlugin
{
    /**
     * Constructor.
     *
     * @param object $subject The object to observe
     * @param array $config An array that holds the plugin configuration
     *
     * @since   2.0.0
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'system-externallogin'],
            Log::ALL,
            ['system-externallogin-deletion', 'system-externallogin-password']
        );
    }

    /**
     * After initialise event.
     *
     * @since   2.0.0
     */
    public function onAfterInitialise()
    {
        // Get the application
        /** @var Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();

        // Get the router
        $router = $app->getRouter();

        // Attach build rules for language SEF
        $router->attachBuildRule([$this, 'buildRule']);
    }

    /**
     * After render event.
     *
     * @since   3.1.0
     */
    public function onAfterRender()
    {
        /** @var Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        $app->setUserState('users.login.form.data.return', null);
    }

    /**
     * Redirect to com_externallogin in case of login view.
     *
     * @param Joomla\CMS\Router\Router $router Router
     * @param Joomla\CMS\Uri\Uri $uri URI
     *
     * @since   2.0.0
     */
    public function buildRule(&$router, &$uri)
    {
        /** @var Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();

        if (
            $app->isClient('site')
            && $uri->getVar('option') == 'com_users'
            && $uri->getVar('view') == 'login'
            && PluginHelper::isEnabled('authentication', 'externallogin')
        ) {
            $redirect = $app->getUserState('com_externallogin.redirect');

            if ($redirect) {
                $app->redirect(Route::_('index.php?Itemid=' . $redirect, true));
                return;
            }
            $item = ComponentHelper::getParams('com_externallogin')->get('unauthorized_redirect_menuitem');

            if ($item == -1) {
                $uri->setVar('option', 'com_externallogin');
            } elseif ($item) {
                $app->redirect(Route::_('index.php?Itemid=' . $item, true));
            }
        }
    }

    /**
     * Remove server information about a user.
     *
     * Method is called after user data is deleted from the database
     *
     * @param array $user Holds the user data
     * @param bool $success True if user was successfully stored in the database
     * @param string $msg Message
     *
     * @return bool
     *
     * @since   2.0.0
     */
    public function onUserAfterDelete($user, $success, $msg)
    {
        $dbo = Factory::getContainer()->get(DatabaseInterface::class);
        $dbo->setQuery($dbo->getQuery(true)->select('server_id')->from('#__externallogin_users')->where('user_id = ' . (int) $user['id']));
        $sid = $dbo->loadResult();
        /** @var ExternalloginTable */
        $server = Table::getInstance('Server', 'ExternalloginTable');
        $user = Factory::getApplication()->getIdentity();

        if ($server->load($sid)) {
            if (!$success) {
                if ($server->params->get('log_user_delete', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Unsuccessful deletion of user "' . $user['username'] . '" by user "' .
                            $user->username . '" on server ' . $sid,
                            Log::WARNING,
                            'system-externallogin-deletion'
                        )
                    );
                }

                return false;
            } else {
                $dbo = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $dbo->getQuery(true);
                $query->delete('#__externallogin_users')->where('user_id = ' . (int) $user['id']);
                $dbo->setQuery($query);
                $dbo->execute();

                if ($server->params->get('log_user_delete', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Successful deletion of user "' . $user['username'] . '" by user "' .
                            $user->username . '" on server ' . $sid,
                            Log::INFO,
                            'system-externallogin-deletion'
                        )
                    );
                }

                return true;
            }
        }
        return false;
    }

    /**
     * Utility method to act on a user after it has been saved.
     *
     * This method sends a registration email to new users created in the backend.
     *
     * @param array $old holds the old user data
     * @param bool $isnew true if a new user is stored
     * @param array $new holds the new user data
     *
     * @return bool
     *
     * @since   2.0.0
     */
    public function onUserBeforeSave($old, $isnew, $new)
    {
        if ($new['password'] != '') {
            $dbo = Factory::getContainer()->get(DatabaseInterface::class);
            $dbo->setQuery($dbo->getQuery(true)->select('server_id')->from('#__externallogin_users')->where('user_id = ' . (int) $new['id']));
            $sid = $dbo->loadResult();
            /** @var ExternalloginTable */
            $server = Table::getInstance('Server', 'ExternalloginTable');

            if ($server->load($sid) && !$server->params->get('allow_change_password', 0)) {
                $dbo = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $dbo->getQuery(true);
                $query->select('COUNT(*)');
                $query->from('#__externallogin_users AS e');
                $query->where('e.user_id = ' . (int) $new['id']);
                $query->leftJoin('#__users AS u ON u.id = e.user_id');
                $query->where('u.password = ' . $dbo->quote(''));
                $dbo->setQuery($query);

                if ($dbo->loadResult() > 0) {
                    if ($server->params->get('log_user_change_password', 0)) {
                        Log::add(
                            new ExternalloginLogEntry(
                                'Attempt to change password for user "' . $new['username'] . '" on server ' . $sid,
                                Log::WARNING,
                                'system-externallogin-deletion'
                            )
                        );
                    }

                    Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_EXTERNALLOGIN_WARNING_PASSWORD_MODIFIED'), 'notice');

                    return false;
                }
            }
        }

        return true;
    }
}
