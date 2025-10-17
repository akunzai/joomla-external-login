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

use Joomla\CMS\Log\Log;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * External Login - External Login entry.
 *
 * @since       2.1.0
 */
class ExternalloginLogEntry extends Joomla\CMS\Log\LogEntry
{
    /**
     * Constructor.
     *
     * @param string $message the message to log
     * @param string $priority message priority based on {$this->priorities}
     * @param string $category Type of entry
     * @param string $date Date of entry (defaults to now if not specified or blank)
     *
     * @since   11.1
     */
    public function __construct($message, $priority = Log::INFO, $category = '', $date = null)
    {
        if (empty($date)) {
            [$microtime, $time] = explode(' ', microtime());
            $date = date('Y-m-d H:i:s', $time) . trim($microtime, '0');
        }

        parent::__construct($message, $priority, $category, $date);
    }
}
