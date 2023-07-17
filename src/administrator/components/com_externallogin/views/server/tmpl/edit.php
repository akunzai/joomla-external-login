<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// No direct access to this file
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4.0.0', '<')) {
    HTMLHelper::_('behavior.tooltip');
    HTMLHelper::_('behavior.formvalidation');
} else {
    HTMLHelper::_('jquery.framework');
}

$fieldSets = $this->form->getFieldsets();
$fistTabName = array_key_first($fieldSets);
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'server.cancel' || document.formvalidator.isValid(document.id('server-form'))) {
			Joomla.submitform(task, document.getElementById('server-form'));
		} else {
			alert(Joomla.Text._('JGLOBAL_VALIDATION_FORM_FAILED', 'Some values are unacceptable'));
		}
	}
</script>

<form action="<?php echo Route::_('index.php?option=com_externallogin&id=' . $this->item->id); ?>" method="post" name="adminForm" id="server-form" class="form-validate form-horizontal">
	<div class="row-fluid">
		<div class="span10">
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'configTabs', ['active' => $fistTabName]); ?>
			<?php foreach ($fieldSets as $name => $fieldSet) : ?>
				<?php $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'configTabs', $name, Text::_($label)); ?>
				<?php if (isset($fieldSet->description) && !empty($fieldSet->description)) : ?>
					<p class="tab-description"><?php echo Text::_($fieldSet->description); ?></p>
				<?php endif; ?>
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<div class="control-group">
						<?php if (!$field->hidden && $name != "permissions") : ?>
							<div class="control-label">
								<?php echo $field->label; ?>
							</div>
						<?php endif; ?>
						<div class="<?php if ($name != "permissions") : ?>controls<?php endif; ?>">
							<?php echo $field->input; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php endforeach; ?>
		</div>
	</div>
	<div>
		<input type="hidden" name="plugin" value="<?php echo htmlspecialchars($this->item->plugin, ENT_COMPAT, 'UTF-8'); ?>" />
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>