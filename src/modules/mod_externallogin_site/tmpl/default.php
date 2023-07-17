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

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

// No direct access to this file
defined('_JEXEC') or die;

?>
<?php if (Factory::getUser()->guest) : ?>
	<div class="externallogin<?php echo htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8'); ?>">
		<form action="<?php echo Route::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="external-login">
			<fieldset class="loginform">
				<?php
                if ($enabled) :
                    switch ($count):
                        case 0:
                            require ModuleHelper::getLayoutPath('mod_externallogin_site', 'zero');
                            break;
                        case 1:
                            require ModuleHelper::getLayoutPath('mod_externallogin_site', 'alone');
                            break;
                        default:
                            require ModuleHelper::getLayoutPath('mod_externallogin_site', 'form');
                            break;
                    endswitch;
                else :
                    require ModuleHelper::getLayoutPath('mod_externallogin_site', 'disabled');
                endif;
?>
			</fieldset>
		</form>
		<div class="clr"></div>
	</div>
<?php elseif ($params->get('show_logout', 0)) : ?>
	<div class="externallogin<?php echo htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8'); ?>">
		<?php require ModuleHelper::getLayoutPath('mod_externallogin_site', 'logout'); ?>
	</div>
	<?php if ($params->get('show_logout_local', 0)) : ?>
		<div class="externallogin<?php echo htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8'); ?>">
			<form action="<?php echo Route::_('index.php?option=com_users&task=user.logout'); ?>" method="post">
				<div>
					<input type="submit" class="button" value="<?php echo htmlspecialchars(Text::_('MOD_EXTERNALLOGIN_SITE_LOGOUT_LOCAL'), ENT_COMPAT, 'UTF-8'); ?>" />
					<input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()); ?>" />
					<input type="hidden" name="local" value="1" />
					<?php echo HTMLHelper::_('form.token'); ?>
				</div>
			</form>
		</div>
	<?php endif; ?>
<?php endif; ?>