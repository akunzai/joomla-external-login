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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Download Model of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginModelDownload extends \Joomla\CMS\MVC\Model\BaseDatabaseModel
{
    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     *
     * @note  Calling getState in this method will result in recursion.
     *
     * @see  JModel::populateState
     *
     * @since  2.0.0
     */
    protected function populateState()
    {
        // Get the pk of the record from the request.
        $pk = Factory::getApplication()->input->getInt('id');
        $this->setState($this->getName() . '.id', $pk);
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   string  $type    The table type to instantiate
     * @param   string  $prefix  A prefix for the table class name. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return	Table  A database object
     *
     * @see     JModel::getTable
     *
     * @since	2.0.0
     */
    public function getTable($type = 'Server', $prefix = 'ExternalloginTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Get file name
     *
     * @return	string	The file name
     *
     * @since	1.6
     */
    public function getBaseName()
    {
        $table = $this->getTable();

        if (!$table->load($this->getState($this->getName() . '.id'))) {
            throw new Exception(Text::_('COM_EXTERNALLOGIN_ERROR_CANNOT_DOWNLOAD'));
        }
        return Factory::getConfig()->get('sitename') . '_' . $table->title . '_' . Factory::getDate();
    }

    /**
     * Get the content
     *
     * @return	void
     *
     * @since	1.6
     */
    public function getContent()
    {
        $file = fopen('php://output', 'w');
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.username, a.name, a.email');
        $query->from('#__users AS a');
        $query->leftJoin('#__externallogin_users AS e ON e.user_id = a.id');
        $query->where('e.server_id = ' . (int) $this->getState($this->getName() . '.id'));
        $query->leftJoin('#__user_usergroup_map AS g ON g.user_id = a.id');
        $query->group('a.id');
        $query->select('GROUP_CONCAT(g.group_id SEPARATOR ",")');
        $db->setQuery($query);
        $results = $db->loadRowList();

        foreach ($results as $result) {
            fputcsv($file, $result);
        }

        fclose($file);
    }
}
