<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

// No direct access to this file
defined('_JEXEC') or die;

/**
 * External Login - External Login logger.
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.1.0
 */
class ExternalloginLogger extends \Joomla\CMS\Log\Logger\DatabaseLogger
{
    /**
     * Method to add an entry to the log.
     *
     * @param   \Joomla\CMS\Log\LogEntry  $entry  The log entry object to add to the log.
     *
     * @return  void
     *
     * @since   2.1.0
     */
    public function addEntry(\Joomla\CMS\Log\LogEntry $entry)
    {
        if ($entry instanceof ExternalloginLogEntry) {
            // Connect to the database if not connected.
            if (empty($this->db)) {
                $this->connect();
            }

            // Convert the date.
            $entry->date = $entry->date->format('U.u');

            $this->db->insertObject($this->table, $entry);
        }
    }
}
