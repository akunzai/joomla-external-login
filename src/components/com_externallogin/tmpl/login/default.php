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
use Joomla\Component\Externallogin\Site\View\Login\HtmlView;

// No direct access to this file
defined('_JEXEC') or die;

/** @var HtmlView $this */
$user = Factory::getApplication()->getIdentity();
if ($user->guest) :
    // The user is not logged in.
    echo $this->loadTemplate('login');
else :
    // The user is already logged in.
    echo $this->loadTemplate('logout');
endif;
