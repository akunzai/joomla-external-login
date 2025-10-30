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

namespace Joomla\Component\Externallogin\Administrator\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Log\Log;

/**
 * ComponentDispatcher class for com_externallogin.
 *
 * @since  5.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * The extension namespace.
     *
     * @var string
     *
     * @since  5.0.0
     */
    protected $namespace = 'Joomla\\Component\\Externallogin';

    /**
     * Method to check component access permission.
     *
     * @throws NotAllowed
     *
     * @since  5.0.0
     */
    protected function checkAccess()
    {
        parent::checkAccess();

        // Additional logging for access checks
        $user = $this->app->getIdentity();
        $view = $this->input->getCmd('view');
        $task = $this->input->getCmd('task');

        // Log access attempts for security auditing
        if ($view || $task) {
            Log::add(
                sprintf(
                    'User %d (%s) accessed com_externallogin with view=%s, task=%s',
                    $user->id,
                    $user->username,
                    $view ?: 'none',
                    $task ?: 'none'
                ),
                Log::DEBUG,
                'com_externallogin'
            );
        }
    }
}
