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

// No direct access to this file
defined('_JEXEC') or die;

// load tooltip behavior
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('dropdown.init');
HTMLHelper::_('formbehavior.chosen', 'select');

/** @var Joomla\CMS\Object\CMSObject */
$state = $this->state;

$listOrder	= $this->escape($state->get('list.ordering'));
$listDirn	= $this->escape($state->get('list.direction'));
?>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<form action="<?php echo Route::_('index.php?option=com_externallogin&view=logs'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)) : ?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar; ?>
			<div class="filter-select hidden-phone">
				<label class="filter-hide-lbl" for="filter_begin"><?php echo Text::_('COM_EXTERNALLOGIN_LABEL_BEGIN'); ?></label>
				<?php echo HTMLHelper::_('calendar', $state->get('filter.begin'), 'filter_begin', 'filter_begin', '%Y-%m-%d', ['class' => 'input-medium', 'size' => 8, 'style' => 'width:146px', 'onchange' => "this.form.fireEvent('submit');this.form.submit()"]); ?>

				<label class="filter-hide-lbl" for="filter_end"><?php echo Text::_('COM_EXTERNALLOGIN_LABEL_END'); ?></label>
				<?php echo HTMLHelper::_('calendar', $state->get('filter.end'), 'filter_end', 'filter_end', '%Y-%m-%d', ['class' => 'input-medium', 'size' => 8, 'style' => 'width:146px', 'onchange' => "this.form.fireEvent('submit');this.form.submit()"]); ?>
			</div>
		</div>
		<div id="j-main-container" class="span10">
		<?php else : ?>
			<div id="j-main-container">
			<?php endif; ?>
			<?php echo $this->loadTemplate('filter'); ?>
			<table class="table table-striped">
				<thead><?php echo $this->loadTemplate('head'); ?></thead>
				<tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
				<tbody><?php echo $this->loadTemplate('body'); ?></tbody>
			</table>
			<div>
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
				<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
			</div>
</form>