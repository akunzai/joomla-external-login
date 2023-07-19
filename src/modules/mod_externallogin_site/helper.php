<?php

/**
 * @package     External_Login
 * @subpackage  External Login Module
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_externallogin/models', 'ExternalloginModel');

/**
 * Module helper class
 *
 * @package     External_Login
 * @subpackage  External Login Module
 *
 * @since       2.0.0
 */
abstract class ModExternalloginsiteHelper
{
    /**
     * Get the URLs of servers
     *
     * @param   Registry  $params  Module parameters
     *
     * @return  array  Array of URL
     */
    public static function getListServersURL($params)
    {
        $app = Factory::getApplication();
        $redirect = $app->input->get('redirect', $app->getUserState('users.login.form.data.return'));

        $redirect = $redirect ? urlencode($redirect) : $params->get('redirect');

        $isHome = in_array(substr((string)Uri::getInstance(), strlen(Uri::base())), ['', 'index.php']);
        $noRedirect = $params->get('noredirect');

        // Get an instance of the generic articles model
        /** @var ExternalloginModelServers */
        $model = BaseDatabaseModel::getInstance('Servers', 'ExternalloginModel', ['ignore_request' => true]);
        if (!$model) {
            return [];
        }
        $model->setState('filter.published', 1);
        $model->setState('filter.enabled', 1);
        $model->setState('filter.servers', $params->get('server'));
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);
        $model->setState('list.ordering', 'a.ordering');
        $model->setState('list.direction', 'ASC');
        $items = $model->getItems();

        foreach ($items as $i => $item) {
            $item->params = new Registry($item->params);
            $url = 'index.php?option=com_externallogin&view=server&server=' . $item->id;

            if ($noRedirect && !$isHome) {
                $url .= '&noredirect=1';
            } elseif (!empty($redirect)) {
                $url .= '&redirect=' . $redirect;
            }

            $item->url = $url;
        }

        return $items;
    }

    /**
     * Retrieve the url where the user should be returned after logging out
     *
     * @param   \Joomla\Registry\Registry  $params  module parameters
     *
     * @return string
     */
    public static function getLogoutUrl($params)
    {
        $app = Factory::getApplication();
        $item = $app->getMenu()->getItem(
            $params->get(
                'logout_redirect_menuitem',
                ComponentHelper::getComponent('com_externallogin', true)->params->get('logout_redirect_menuitem')
            )
        );

        // Stay on the same page
        $url = Uri::getInstance()->toString();

        if ($item) {
            $lang = '';

            if (Multilanguage::isEnabled() && $item->language !== '*') {
                $lang = '&lang=' . $item->language;
            }

            $url = Route::_('index.php?Itemid=' . $item->id . $lang, $app->get('force_ssl') === 2 ? 1 : 2);
        }

        // We are forced to encode the url in base64 as com_users uses this encoding
        return base64_encode($url);
    }
}
