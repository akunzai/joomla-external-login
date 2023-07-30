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
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

// No direct access to this file
defined('_JEXEC') or die;

// Include the component HTML helpers.
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$user = Factory::getUser();
$ordering = $this->state->get('list.ordering') == 'a.ordering';
$plugins = ArrayHelper::pivot(ExternalloginHelper::getPlugins(), 'value');
?>
<?php foreach ($this->items as $i => $item) : ?>
	<tr class="row<?php echo $i % 2; ?>">
		<td class="center">
			<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
		</td>
		<td>
			<?php echo $this->escape($item->username); ?>
		</td>
		<td>
			<?php echo $this->escape($item->name); ?>
		</td>
		<td>
			<?php echo $this->escape($item->email); ?>
		</td>
		<td>
			<?php if (isset($item->plugin)) : ?>
				<?php echo isset($plugins[$item->plugin]) ? $this->escape(Text::_($plugins[$item->plugin]['text'])) : Text::_('COM_EXTERNALLOGIN_GRID_SERVER_DISABLED'); ?>
			<?php endif; ?>
		</td>
		<td>
			<?php echo $this->escape($item->title); ?>
		</td>
		<td class="center">
			<?php echo HTMLHelper::_('ExternalloginHtml.Users.joomla', $item->joomla, $i, isset($item->plugin)); ?>
		</td>
		<td class="center">
			<?php if (isset($item->plugin)) : ?>
				<?php echo HTMLHelper::_('ExternalloginHtml.Users.externallogin', 1, $i, $item->joomla); ?>
			<?php else : ?>
				<button value="<?php echo Route::_('index.php?option=com_externallogin&amp;view=servers&amp;layout=modal&amp;tmpl=component', true); ?>" class="btn btn-micro modal" onclick="document.getElementById('cb<?php echo $i; ?>').checked = true; return true;" title="<?php echo addslashes(htmlspecialchars(Text::_('COM_EXTERNALLOGIN_GRID_USER_EXTERNALLOGIN_ENABLE'), ENT_COMPAT, 'UTF-8')); ?>" data-toggle="modal" data-target="#modal-publish">
					<span class="icon-unpublish"></span>
				</button>
			<?php endif; ?>
		</td>
		<td class="right">
			<?php echo $item->id; ?>
		</td>
	</tr>
<?php endforeach; ?>