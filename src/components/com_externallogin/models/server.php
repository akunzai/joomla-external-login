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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Server Model of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginModelServer extends Joomla\CMS\MVC\Model\ItemModel
{
    /**
     * Method to auto-populate the model state.
     *
     * @note  Calling getState in this method will result in recursion.
     *
     * @see  JModel::populateState
     * @since  2.0.0
     */
    protected function populateState()
    {
        $app = Factory::getApplication();
        $id = $app->input->get('server', 0, 'uint');
        $this->setState('server.id', $id);
        $redirect = $app->input->get('redirect', '', 'RAW');
        $this->setState('server.redirect', $redirect);
        $noredirect = $app->input->get('noredirect');
        $this->setState('server.noredirect', $noredirect);
        parent::populateState();
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param string $type The table type to instantiate
     * @param string $prefix A prefix for the table class name. Optional.
     * @param array $config Configuration array for model. Optional.
     *
     * @return Table A database object
     *
     * @see     JModel::getTable
     * @since	2.0.0
     */
    public function getTable($type = 'Server', $prefix = 'ExternalloginTable', $config = [])
    {
        return $this->getMVCFactory()->createTable($type, $prefix, $config);
    }

    /**
     * Returns the server.
     *
     * @return Uri|string the service URI
     *
     * @since	2.0.0
     */
    public function getItem($pk = null)
    {
        // Load the server
        $id = $this->getState('server.id');
        $item = $this->getTable();

        if (!$item->load($id) || $item->published != 1) {
            throw new Exception(Text::_('COM_EXTERNALLOGIN_ERROR_SERVER_UNPUBLISHED'));
        }

        /** @var Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        $params = is_null($menu) ? new Registry() : $menu->getParams();

        // Compute the url
        $redirect = $this->getState(
            'server.redirect',
            $params->get(
                'redirect',
                $item->params->get(
                    'redirect',
                    ComponentHelper::getParams('com_externallogin')->get('redirect')
                )
            )
        );
        $noredirect = $this->getState(
            'server.noredirect',
            $item->params->get(
                'noredirect',
                ComponentHelper::getParams('com_externallogin')->get('noredirect')
            )
        );

        if (!empty($redirect) && !$noredirect) {
            $url = ExternalloginHelper::url($redirect);
        } else {
            $url = $app->input->server->getString('HTTP_REFERER');

            if (empty($url) || !Uri::isInternal($url)) {
                $url = Route::_('index.php', true, $app->get('force_ssl') == 2);
            }
        }

        // Compute the URI
        $uri = Uri::getInstance($url);

        // Return the service/URL
        $user = $user = $this->getCurrentUser();
        if (!$user->guest) {
            return $uri;
        }
        $app->setUserState('com_externallogin.server', $item->id);
        $results = $app->triggerEvent('onGetLoginUrl', [$item, $uri]);

        if (empty($results)) {
            throw new Exception(Text::_('COM_EXTERNALLOGIN_ERROR_OCCURS'));
        }
        return $results[0];
    }
}
