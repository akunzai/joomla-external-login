<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://github.com/akunzai/joomla-external-login
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Login Model of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginModelLogin extends \Joomla\CMS\MVC\Model\ListModel
{
    /**
     * Method to auto-populate the model state.
     *
     * @param   string|null  $ordering   Column for ordering
     * @param   string|null  $direction  Direction of ordering
     *
     * @return  void
     *
     * @note  Calling getState in this method will result in recursion.
     *
     * @see  JModelList::populateState
     *
     * @since  2.0.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        /** @var \Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $redirect = $app->input->get('redirect', $app->getUserState('users.login.form.data.return'));
        $this->setState('server.redirect', $redirect);
        $noredirect = $app->input->get('noredirect');
        $this->setState('server.noredirect', $noredirect);

        // List state information.
        parent::populateState('a.ordering', 'asc');
    }

    /**
     * Method to get a JDatabaseQuery object for retrieving the data set from a database.
     *
     * @return  object  A JDatabaseQuery object to retrieve the data set.
     *
     * @see  JModelList::getListQuery
     *
     * @since  2.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db = Factory::getDBO();
        $query = $db->getQuery(true);

        // Select some fields
        $query->select('a.*');

        // From the externallogin_servers table
        $query->from($db->quoteName('#__externallogin_servers') . ' as a');

        // Join over the users for the enabled plugins.
        $query->join(
            'LEFT',
            '#__extensions AS e ON ' .
                $db->quoteName('e.type') . '=' . $db->quote('plugin') . ' AND ' .
                $query->concatenate([$db->quoteName('e.folder'), $db->quoteName('e.element')], '.') . '=' . $db->quoteName('a.plugin')
        );
        $query->where('e.enabled = 1');

        // Filter by published state
        $query->where('a.published = 1');

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Method to get a list of servers.
     *
     * @return  array  A list of servers.
     *
     * @since  2.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();
        /** @var \Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        $menu = $app->getMenu()->getActive();

        if ($menu) {
            $params = $menu->getParams();
        } else {
            $params = new Registry();
        }

        foreach ($items as $i => $item) {
            $item->params = new Registry($item->params);
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

            $url = 'index.php?option=com_externallogin&view=server&server=' . $item->id;

            if ($noredirect) {
                $url .= '&noredirect=1';
            } elseif (!empty($redirect)) {
                if (is_numeric($redirect)) {
                    $url .= '&redirect=' . $redirect;
                } else {
                    $url .= '&redirect=' . urlencode($redirect);
                }
            }

            $item->url = $url;
        }

        return $items;
    }
}
