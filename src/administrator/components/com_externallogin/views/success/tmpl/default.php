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

// No direct access to this file
defined('_JEXEC') or die;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->addInlineScript('window.parent.addEventListener(\'DOMContentLoaded\', function() {
	window.top.setTimeout(function() { window.parent.Joomla.Modal.getCurrent().close(); }, 2000);
});');
