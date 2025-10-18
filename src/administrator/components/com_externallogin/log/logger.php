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

// No direct access to this file
defined('_JEXEC') or die;

/**
 * External Login - External Login logger.
 *
 * @since       2.1.0
 */
class ExternalloginLogger extends Joomla\CMS\Log\Logger\DatabaseLogger
{
    /**
     * Method to add an entry to the log.
     *
     * @param Joomla\CMS\Log\LogEntry $entry the log entry object to add to the log
     *
     * @since   2.1.0
     */
    public function addEntry(Joomla\CMS\Log\LogEntry $entry)
    {
        if ($entry instanceof ExternalloginLogEntry) {
            // Connect to the database if not connected.
            if (empty($this->db)) {
                $this->connect();
            }

            // Convert the date to timestamp string for database storage
            $dbEntry = clone $entry;
            $dbEntry->date = $entry->date->format('U.u');

            $this->db->insertObject($this->table, $dbEntry);
        }
    }
}
