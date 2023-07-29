<?php

/**
 * @package     External_Login
 * @subpackage  External Login Module
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://github.com/akunzai/joomla-external-login
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
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
abstract class ModExternalloginadminHelper
{
    /**
     * Get the URLs of servers
     *
     * @param   \Joomla\Registry\Registry  $params  Module parameters
     *
     * @return  array  Array of URL
     */
    public static function getListServersURL($params)
    {
        $app = Factory::getApplication();
        $uri = Uri::getInstance();

        // Get an instance of the generic articles model
        /** @var ExternalloginModelServers */
        $model = BaseDatabaseModel::getInstance('Servers', 'ExternalloginModel', ['ignore_request' => true]);
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
            $uri->setVar('server', $item->id);
            $results = $app->triggerEvent('onGetLoginUrl', [$item, Route::_($uri, true)]);

            if (!empty($results)) {
                $item->url = $results[0];
            } else {
                unset($items[$i]);
            }
        }

        return $items;
    }
}
