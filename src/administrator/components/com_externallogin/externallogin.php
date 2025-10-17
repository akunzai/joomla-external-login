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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

// No direct access to this file
defined('_JEXEC') or die;

// Access check.
$app = Factory::getApplication();
$user = $app->getIdentity();
if (!$user->authorise('core.manage', 'com_externallogin')) {
    $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
    return;
}

// Require helpers file
require_once dirname(__FILE__) . '/helpers.php';

// Get an instance of the controller prefixed by Externallogin
$mvcFactory = $app->bootComponent('com_externallogin')->getMVCFactory();
$controller = $mvcFactory->createController('Display', 'Administrator', [], $app, $app->input);

// Perform the Request task
$controller->execute($app->input->get('task'));

// Redirect if set by the controller
$controller->redirect();
