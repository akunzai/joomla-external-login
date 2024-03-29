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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

// No direct access to this file
defined('_JEXEC') or die;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$user = version_compare(JVERSION, '4.0.0', '<')
    ? Factory::getUser()
    : Factory::getApplication()->getIdentity();
$ordering = $this->state->get('list.ordering') == 'a.ordering';
$plugins = ArrayHelper::pivot(ExternalloginHelper::getPlugins(), 'value');

if (!count($this->items)) {
    ?>
	<tr class="row0">
		<td colspan="6" class="center">
			<?php echo Text::_('COM_EXTERNALLOGIN_NO_RECORDS'); ?>
		</td>
	</tr>
<?php
} else {
    ?>
	<?php foreach ($this->items as $i => $item) : ?>
		<tr class="row<?php echo $i % 2; ?>">
			<td>
				<a class="pointer" onclick="if (window.parent) {window.parent.document.adminForm.server.value=<?php echo $item->id; ?>;window.close(); window.parent.submitbutton('users.enableExternallogin');}"><?php echo $this->escape($item->title); ?></a>
			</td>
			<?php if (isset($this->globalS)) : ?>
				<td>
					<button class="btn" onclick="if (window.parent) {window.parent.document.adminForm.server.value=<?php echo $item->id; ?>;window.close(); window.parent.submitbutton('users.enableExternalloginGlobal');}"><?php echo Text::_('COM_EXTERNALLOGIN_BUTTON_ACTIVATE_ALL'); ?></button>
				</td>
				<td>
					<button class="btn" onclick="if (window.parent) {window.parent.document.adminForm.server.value=<?php echo $item->id; ?>;window.close(); window.parent.submitbutton('users.disableExternalloginGlobal');}"><?php echo Text::_('COM_EXTERNALLOGIN_BUTTON_DISABLE_ALL'); ?></button>
				</td>
			<?php endif; ?>
			<td>
				<?php echo isset($plugins[$item->plugin]) ? $this->escape(Text::_($plugins[$item->plugin]['text'])) : Text::_('COM_EXTERNALLOGIN_GRID_SERVER_DISABLED'); ?>
			</td>
			<td class="center">
				<?php echo HTMLHelper::_(
				    'ExternalloginHtml.Servers.state',
				    $item->published == 1 ? ($item->enabled == null ? 4 : ($item->enabled == 0 ? 3 : 1)) : $item->published,
				    $i,
				    false
				); ?>
			</td>
			<td class="right">
				<?php echo $item->id; ?>
			</td>
		</tr>
<?php endforeach;
} ?>