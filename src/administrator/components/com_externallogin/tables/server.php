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

use Joomla\Registry\Registry;

// No direct access
defined('_JEXEC') or die;

/**
 * Server Table class of External Login component.
 *
 * @since       0.0.1
 */
class ExternalloginTableServer extends Joomla\CMS\Table\Table
{
    /**
     * The parameter object.
     *
     * @var Registry|string
     */
    public $params;

    /**
     * @var int
     */
    public $ordering;

    /**
     * Constructor.
     *
     * @param object $db Database connector object
     *
     * @see     JTable::__construct
     * @since   2.0.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__externallogin_servers', 'id', $db);
    }

    /**
     * Overloaded load function.
     *
     * @param mixed $keys An optional primary key value to load the row by, or an array of fields to match.  If not
     *                    set the instance property value is used.
     * @param bool $reset true to reset the default values before loading the new row
     *
     * @return bool true on success
     *
     * @see     JTable::load
     * @since   2.0.0
     */
    public function load($keys = null, $reset = true)
    {
        if (!parent::load($keys, $reset)) {
            return false;
        }
        $this->params = isset($this->params)
            ? new Registry($this->params)
            : new Registry();
        return true;
    }

    /**
     * Overloaded store function.
     *
     * @param bool $updateNulls true to update fields even if they are null
     *
     * @return bool true on success
     *
     * @link	http://docs.joomla.org/JTable/store
     * @since   2.0.0
     */
    public function store($updateNulls = false)
    {
        if ($this->ordering == 0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('MAX(ordering)');
            $query->from('#__externallogin_servers');
            $db->setQuery($query);
            $this->ordering = $db->loadResult() + 1;
        }

        if (is_array($this->params)) {
            $this->params = (string) new Registry($this->params);
        }

        if (parent::store($updateNulls)) {
            return $this->reorder();
        } else {
            return false;
        }
    }
}
