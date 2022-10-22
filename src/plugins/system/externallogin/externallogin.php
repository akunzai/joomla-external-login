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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;

// No direct access to this file
defined('_JEXEC') or die;

JLoader::import('joomla.database.table');
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_externallogin/tables');

JLoader::registerAlias('ExternalloginLogger', '\\Joomla\\CMS\\Log\\Logger\\ExternalloginLogger');
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
class PlgSystemExternallogin extends \Joomla\CMS\Plugin\CMSPlugin
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
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'system-externallogin'],
            Log::ALL,
            ['system-externallogin-deletion', 'system-externallogin-password']
        );
    }

    /**
     * After initialise event
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function onAfterInitialise()
    {
        // Get the application
        $app = Factory::getApplication();

        // Get the router
        $router = $app->getRouter();

        // Attach build rules for language SEF
        $router->attachBuildRule([$this, 'buildRule']);
    }

    /**
     * After render event
     *
     * @return  void
     *
     * @since   3.1.0
     */
    public function onAfterRender()
    {
        Factory::getApplication()->setUserState('users.login.form.data.return', null);
    }

    /**
     * Redirect to com_externallogin in case of login view
     *
     * @param   \Joomla\CMS\Router\Router  $router  Router
     * @param   \Joomla\CMS\Uri\Uri     $uri     URI
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function buildRule(&$router, &$uri)
    {
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
     * Remove server information about a user
     *
     * Method is called after user data is deleted from the database
     *
     * @param   array    $user     Holds the user data
     * @param   boolean  $success  True if user was successfully stored in the database
     * @param   string   $msg      Message
     *
     * @return  boolean
     *
     * @since   2.0.0
     */
    public function onUserAfterDelete($user, $success, $msg)
    {
        $dbo = Factory::getDbo();
        $dbo->setQuery($dbo->getQuery(true)->select('server_id')->from('#__externallogin_users')->where('user_id = ' . (int) $user['id']));
        $sid = $dbo->loadResult();
        $server = Table::getInstance('Server', 'ExternalloginTable');

        if ($server->load($sid)) {
            if (!$success) {
                if ($server->params->get('log_user_delete', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Unsuccessful deletion of user "' . $user['username'] . '" by user "' .
                                Factory::getUser()->username . '" on server ' . $sid,
                            Log::WARNING,
                            'system-externallogin-deletion'
                        )
                    );
                }

                return false;
            } else {
                $dbo = Factory::getDbo();
                $query = $dbo->getQuery(true);
                $query->delete('#__externallogin_users')->where('user_id = ' . (int) $user['id']);
                $dbo->setQuery($query);
                $dbo->execute();

                if ($server->params->get('log_user_delete', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Successful deletion of user "' . $user['username'] . '" by user "' .
                                Factory::getUser()->username . '" on server ' . $sid,
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
     * @param   array    $old    Holds the old user data.
     * @param   boolean  $isnew  True if a new user is stored.
     * @param   array    $new    Holds the new user data.
     *
     * @return  boolean
     *
     * @since   2.0.0
     */
    public function onUserBeforeSave($old, $isnew, $new)
    {
        if ($new['password'] != '') {
            $dbo = Factory::getDbo();
            $dbo->setQuery($dbo->getQuery(true)->select('server_id')->from('#__externallogin_users')->where('user_id = ' . (int) $new['id']));
            $sid = $dbo->loadResult();
            $server = Table::getInstance('Server', 'ExternalloginTable');

            if ($server->load($sid) && !$server->params->get('allow_change_password', 0)) {
                $dbo = Factory::getDbo();
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
