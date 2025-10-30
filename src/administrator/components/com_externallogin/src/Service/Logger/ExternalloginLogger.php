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

namespace Joomla\CMS\Log\Logger;

use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Log\Logger\DatabaseLogger;
use Joomla\Component\Externallogin\Administrator\Service\Logger\ExternalloginLogEntry;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * External Login - External Login logger.
 *
 * @since       2.1.0
 */
class ExternalloginLogger extends DatabaseLogger
{
    /**
     * Method to add an entry to the log.
     *
     * @param LogEntry $entry the log entry object to add to the log
     *
     * @since   2.1.0
     */
    public function addEntry(LogEntry $entry)
    {
        if ($entry instanceof ExternalloginLogEntry) {
            // Convert the date to timestamp string for database storage
            $dbEntry = (object) (array) $entry;
            $dbEntry->date = $entry->date->format('U.u');

            $this->db->insertObject($this->table, $dbEntry);
        }
    }
}
