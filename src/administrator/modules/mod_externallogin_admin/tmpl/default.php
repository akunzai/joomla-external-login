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

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die;
?>
<form action="<?php echo Route::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="external-login">
    <?php require ModuleHelper::getLayoutPath('mod_externallogin_admin', 'title'); ?>
    <fieldset class="loginform">
        <?php
        if ($enabled) :
            switch ($count):
                case 0:
                    require ModuleHelper::getLayoutPath('mod_externallogin_admin', 'zero');
                    break;
                case 1:
                    require ModuleHelper::getLayoutPath('mod_externallogin_admin', 'alone');
                    break;
                default:
                    require ModuleHelper::getLayoutPath('mod_externallogin_admin', 'form');
                    break;
            endswitch;
        else :
            require ModuleHelper::getLayoutPath('mod_externallogin_admin', 'disabled');
        endif;
        ?>
    </fieldset>
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
<div class="clr"></div>