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
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access to this file
defined('_JEXEC') or die;

// Require helpers file
require_once dirname(__FILE__) . '/helpers.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers.php';

// Get an instance of the controller prefixed by Externallogin
$controller = BaseController::getInstance('Externallogin');

// Perform the Request task
$controller->execute(Factory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();
