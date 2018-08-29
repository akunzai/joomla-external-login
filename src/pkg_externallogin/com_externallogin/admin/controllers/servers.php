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

// Import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

/**
 * Servers Controller of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginControllerServers extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string      $name    Model name
	 * @param   string      $prefix  Model prefix
	 * @param   array|null  $config  Array of options
	 *
	 * @return  JModel
	 *
	 * @see     JController::getModel
	 *
	 * @since   2.0.0
	 */
	public function getModel($name = 'Server', $prefix = 'ExternalloginModel', $config = null)
	{
		return parent::getModel($name, $prefix, isset($config) ? $config : array('ignore_request' => true));
	}
}
