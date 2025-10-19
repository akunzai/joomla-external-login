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

namespace Joomla\Component\Externallogin\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Servers Controller of External Login component.
 *
 * @since       2.0.0
 */
class ServersController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param string $name Model name
     * @param string $prefix Model prefix
     * @param array|null $config Array of options
     *
     * @return BaseDatabaseModel|false
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Server', $prefix = '', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }
}
