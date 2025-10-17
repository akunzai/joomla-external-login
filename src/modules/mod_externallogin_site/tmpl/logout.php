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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.keepalive');
?>
<form action="<?php echo Route::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form" class="form-vertical">
	<?php if ($params->get('greeting')) : ?>
		<div class="login-greeting">
			<?php if ($params->get('name') == 0) : ?>
				<?php echo Text::sprintf('MOD_EXTERNALLOGIN_SITE_HINAME', htmlspecialchars($user->get('name'), ENT_COMPAT, 'UTF-8')); ?>
			<?php else : ?>
				<?php echo Text::sprintf('MOD_EXTERNALLOGIN_SITE_HINAME', htmlspecialchars($user->get('username'), ENT_COMPAT, 'UTF-8')); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="logout-button">
		<input type="submit" name="Submit" class="btn btn-primary" value="<?php echo Text::_('JLOGOUT'); ?>" />
		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.logout" />
		<input type="hidden" name="return" value="<?php echo $return; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>