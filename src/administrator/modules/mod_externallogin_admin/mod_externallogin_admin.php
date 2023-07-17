<?php

/**
 * @package     External_Login
 * @subpackage  External Login Module
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;

// No direct access to this file
defined('_JEXEC') or die;

JLoader::import('joomla.plugin.helper');
JLoader::import('joomla.application.component.helper');

require_once dirname(__FILE__) . '/helper.php';

$enabled = ComponentHelper::getComponent('com_externallogin', true)->enabled && PluginHelper::isEnabled('authentication', 'externallogin');
$servers = ModExternalloginadminHelper::getListServersURL($params);
$count = count($servers);
require ModuleHelper::getLayoutPath('mod_externallogin_admin', $params->get('layout', 'default'));
