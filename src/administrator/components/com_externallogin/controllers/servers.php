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
 * Servers Controller of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginControllerServers extends \Joomla\CMS\MVC\Controller\AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string      $name    Model name
     * @param   string      $prefix  Model prefix
     * @param   array|null  $config  Array of options
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @see     JControllerAdmin::getModel
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Server', $prefix = 'ExternalloginModel', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }
}
