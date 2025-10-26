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

namespace Joomla\Plugin\System\Externallogin\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Router\SiteRouter;
use Joomla\Component\Externallogin\Administrator\Service\Logger\ExternalloginLogEntry;
use Joomla\Component\Externallogin\Administrator\Table\ServerTable;
use Joomla\Database\DatabaseInterface;

/**
 * External Login - System plugin.
 */
class Externallogin extends CMSPlugin
{
    /**
     * Constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->loadLanguage();
        require_once JPATH_ADMINISTRATOR . '/components/com_externallogin/src/Service/Logger/ExternalloginLogger.php';
        Log::addLogger(
            ['logger' => 'externallogin', 'db_table' => '#__externallogin_logs', 'plugin' => 'system-externallogin'],
            Log::ALL,
            ['system-externallogin-deletion', 'system-externallogin-password']
        );
    }

    /**
     * After initialise event.
     */
    public function onAfterInitialise(): void
    {
        $router = Factory::getContainer()->get(SiteRouter::class);
        $router->attachBuildRule([$this, 'buildRule']);
    }

    /**
     * After render event.
     */
    public function onAfterRender(): void
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();
        $app->setUserState('users.login.form.data.return', null);
    }

    /**
     * Redirect to com_externallogin in case of login view.
     */
    public function buildRule(&$router, &$uri): void
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();

        if (
            $app->isClient('site')
            && $uri->getVar('option') == 'com_users'
            && $uri->getVar('view') == 'login'
            && PluginHelper::isEnabled('authentication', 'externallogin')
        ) {
            $redirect = $app->getUserState('com_externallogin.redirect');

            if ($redirect) {
                $app->redirect(Route::_('index.php?Itemid=' . $redirect, true), 302);
                return;
            }

            $item = ComponentHelper::getParams('com_externallogin')->get('unauthorized_redirect_menuitem');

            if ($item == -1) {
                $uri->setVar('option', 'com_externallogin');
            } elseif ($item) {
                $app->redirect(Route::_('index.php?Itemid=' . $item, true), 302);
            }
        }
    }

    /**
     * Remove server information about a user after deletion.
     */
    public function onUserAfterDelete($user, $success, $msg)
    {
        $dbo = Factory::getContainer()->get(DatabaseInterface::class);
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('server_id')
                ->from('#__externallogin_users')
                ->where('user_id = ' . (int) $user['id'])
        );
        $sid = $dbo->loadResult();
        $server = new ServerTable($dbo);
        $currentUser = Factory::getApplication()->getIdentity();

        if ($server->load($sid)) {
            if (!$success) {
                if ($server->params->get('log_user_delete', 0)) {
                    Log::add(
                        new ExternalloginLogEntry(
                            'Unsuccessful deletion of user "' . $user['username'] . '" by user "' .
                            $currentUser->username . '" on server ' . $sid,
                            Log::WARNING,
                            'system-externallogin-deletion'
                        )
                    );
                }

                return false;
            }

            $dbo = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $dbo->getQuery(true);
            $query->delete('#__externallogin_users')->where('user_id = ' . (int) $user['id']);
            $dbo->setQuery($query);
            $dbo->execute();

            if ($server->params->get('log_user_delete', 0)) {
                Log::add(
                    new ExternalloginLogEntry(
                        'Successful deletion of user "' . $user['username'] . '" by user "' .
                        $currentUser->username . '" on server ' . $sid,
                        Log::INFO,
                        'system-externallogin-deletion'
                    )
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Utility method to act on a user before saving.
     */
    public function onUserBeforeSave($old, $isnew, $new)
    {
        if ($new['password'] != '') {
            $dbo = Factory::getContainer()->get(DatabaseInterface::class);
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select('server_id')
                    ->from('#__externallogin_users')
                    ->where('user_id = ' . (int) $new['id'])
            );
            $sid = $dbo->loadResult();
            $dbo = Factory::getContainer()->get(DatabaseInterface::class);
            $server = new ServerTable($dbo);

            if ($server->load($sid) && !$server->params->get('allow_change_password', 0)) {
                $dbo = Factory::getContainer()->get(DatabaseInterface::class);
                $query = $dbo->getQuery(true);
                $query->select('COUNT(*)')
                    ->from('#__externallogin_users AS e')
                    ->where('e.user_id = ' . (int) $new['id'])
                    ->leftJoin('#__users AS u ON u.id = e.user_id')
                    ->where('u.password = ' . $dbo->quote(''));
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
