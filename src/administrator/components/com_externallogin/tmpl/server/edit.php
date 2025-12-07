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

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \Joomla\Component\Externallogin\Administrator\View\Server\HtmlView $this */

/** @var \Joomla\CMS\Application\CMSApplication $app */
$app = Factory::getApplication();
$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('jquery')
    ->useScript('jquery-noconflict')
    ->useScript('form.validate');

$fieldSets = $this->form->getFieldsets();
$fistTabName = array_key_first($fieldSets);
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'server.cancel' || document.formvalidator.isValid(document.getElementById('server-form'))) {
			Joomla.submitform(task, document.getElementById('server-form'));
		} else {
			alert(Joomla.Text._('JGLOBAL_VALIDATION_FORM_FAILED', 'Some values are unacceptable'));
		}
	}
</script>

<form action="<?php echo Route::_('index.php?option=com_externallogin&id=' . $this->item->id); ?>" method="post" name="adminForm" id="server-form" class="form-validate form-horizontal">
	<div class="row-fluid">
		<div class="span10">
			<?php echo HTMLHelper::_('uitab.startTabSet', 'configTabs', ['active' => $fistTabName]); ?>
			<?php foreach ($fieldSets as $name => $fieldSet) : ?>
				<?php $label = empty($fieldSet->label) ? 'COM_CONFIG_' . $name . '_FIELDSET_LABEL' : $fieldSet->label; ?>
				<?php echo HTMLHelper::_('uitab.addTab', 'configTabs', $name, Text::_($label)); ?>
				<?php if (isset($fieldSet->description) && !empty($fieldSet->description)) : ?>
					<p class="tab-description"><?php echo Text::_($fieldSet->description); ?></p>
				<?php endif; ?>
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<?php
                        $options = [];

				    if ($name === 'permissions') {
				        $options['hiddenLabel'] = true;
				    }

				    echo $field->renderField($options);
				    ?>
				<?php endforeach; ?>
				<?php echo HTMLHelper::_('uitab.endTab'); ?>
			<?php endforeach; ?>
			<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
		</div>
	</div>
	<div>
		<input type="hidden" name="plugin" value="<?php echo htmlspecialchars($this->item->plugin, ENT_COMPAT, 'UTF-8'); ?>" />
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>