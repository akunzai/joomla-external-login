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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4.0.0', '<')) {
    // load tooltip behavior
    HTMLHelper::_('behavior.tooltip');
}

// Set url for form action
if (!isset($this->globalS)) {
    $frmAct = Route::_('index.php?option=com_externallogin&view=servers&tmpl=component&layout=modal');
} else {
    $frmAct = Route::_('index.php?option=com_externallogin&view=servers&tmpl=component&layout=modal&globalS=1');
}

?>
<form action="<?php echo $frmAct; ?>" method="post" name="adminForm" id="adminForm">
	<?php echo $this->loadTemplate('filter'); ?>
	<table class="table table-striped">
		<thead><?php echo $this->loadTemplate('head'); ?></thead>
		<tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
		<tbody><?php echo $this->loadTemplate('body'); ?></tbody>
	</table>
	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>