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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Logs Model of External Login component.
 *
 * @since       2.1.0
 */
class ExternalloginModelLogs extends Joomla\CMS\MVC\Model\ListModel
{
    /**
     * Valid filter fields or ordering.
     *
     * @var array
     *
     * @see  JModelList::$filter_fields
     * @since  2.1.0
     */
    protected $filter_fields = ['a.message', 'a.priority', 'a.category', 'a.date'];

    /**
     * Method to auto-populate the model state.
     *
     * @param string $ordering Table name for ordering
     * @param string $direction Direction for ordering
     *
     * @note  Calling getState in this method will result in recursion.
     *
     * @see  JModelList::populateState
     * @since  2.1.0
     */
    protected function populateState($ordering = null, $direction = null)
    {
        // Adjust the context to support modal layouts.
        if ($layout = Factory::getApplication()->input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $priority = $this->getUserStateFromRequest($this->context . '.filter.priority', 'filter_priority', '');
        $this->setState('filter.priority', $priority);

        $category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '');
        $this->setState('filter.category', $category);

        $begin = $this->getUserStateFromRequest($this->context . '.filter.begin', 'filter_begin', '');
        $this->setState('filter.begin', $begin);

        $end = $this->getUserStateFromRequest($this->context . '.filter.end', 'filter_end', '');
        $this->setState('filter.end', $end);

        // List state information.
        parent::populateState('a.date', 'desc');
    }

    /**
     * Method to get a JDatabaseQuery object for retrieving the data set from a database.
     *
     * @return object a JDatabaseQuery object to retrieve the data set
     *
     * @see  JModelList::getListQuery
     * @since  2.1.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // Select some fields
        $query->select('a.priority, a.category, a.date, a.message');

        // From the externallogin_servers table
        $query->from($db->quoteName('#__externallogin_logs') . ' as a');

        // Filter by search in message.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->Quote('%' . $db->escape($search, true) . '%');
            $query->where('a.message LIKE ' . $search);
        }

        // Filter by category
        $category = $this->getState('filter.category');

        if (!empty($category)) {
            $query->where('a.category = ' . $db->quote($category));
        }

        // Filter by priority
        $priority = $this->getState('filter.priority');

        if (is_numeric($priority)) {
            $query->where('a.priority = ' . (int) $priority);
        }

        // Filter by begin date
        $begin = $this->getState('filter.begin');

        if (!empty($begin)) {
            $begin = Factory::getDate($begin);
            $query->where('a.date >= ' . $db->quote($begin->toUnix()));
        }

        // Filter by end date
        $end = $this->getState('filter.end');

        if (!empty($end)) {
            $end = Factory::getDate($end);
            $query->where('a.date < ' . $db->quote($end->toUnix() + 24 * 60 * 60));
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Get file name.
     *
     * @return string The file name
     *
     * @since	2.1.0
     */
    public function getBaseName()
    {
        $app = Factory::getApplication();
        return $app->getConfig()->get('sitename') . '_externallogin-logs_' . Factory::getDate();
    }

    /**
     * Get the content.
     *
     * @since	2.1.0
     */
    public function getContent()
    {
        $file = fopen('php://output', 'w');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery($this->getListQuery());
        $results = $db->loadAssocList();

        foreach ($results as $result) {
            $result['priority'] = Text::_('COM_EXTERNALLOGIN_GRID_LOG_PRIORITY_' . $result['priority']);
            [$time, $microtime] = explode('.', $result['date']);
            $result['date'] = date('Y-m-d H:i:s', $time) . '.' . $microtime;
            fputcsv($file, $result);
        }

        fclose($file);
    }

    /**
     * Delete items.
     *
     * @since	2.1.0
     */
    public function delete()
    {
        // Create a new query object.
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // Delete
        $query->delete();

        // From the externallogin_servers table
        $query->from($db->quoteName('#__externallogin_logs'));

        // Filter by search in message.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->Quote('%' . $db->escape($search, true) . '%');
            $query->where('message LIKE ' . $search);
        }

        // Filter by category
        $category = $this->getState('filter.category');

        if (!empty($category)) {
            $query->where('category = ' . $db->quote($category));
        }

        // Filter by priority
        $priority = $this->getState('filter.priority');

        if (is_numeric($priority)) {
            $query->where('priority = ' . (int) $priority);
        }

        // Filter by begin date
        $begin = $this->getState('filter.begin');

        if (!empty($begin)) {
            $begin = Factory::getDate($begin);
            $query->where('date >= ' . $db->quote($begin->toUnix()));
        }

        // Filter by end date
        $end = $this->getState('filter.end');

        if (!empty($end)) {
            $end = Factory::getDate($end);
            $query->where('date < ' . $db->quote($end->toUnix() + 24 * 60 * 60));
        }

        $db->setQuery($query);

        $db->execute();
        /** @var Joomla\CMS\Application\CMSApplication */
        $app = Factory::getApplication();
        $app->setUserState($this->context . '.filter.search', '');
        $app->setUserState($this->context . '.filter.priority', '');
        $app->setUserState($this->context . '.filter.category', '');
        $app->setUserState($this->context . '.filter.begin', '');
        $app->setUserState($this->context . '.filter.end', '');
        $app->enqueueMessage(Text::_('COM_EXTERNALLOGIN_MSG_LOGS_FILTER_RESET'), 'notice');
    }
}
