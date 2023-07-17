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

// No direct access to this file
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4.0.0', '<')) {
	// load tooltip behavior
	HTMLHelper::_('behavior.tooltip');
}
?>
<fieldset id="filter">
	<div class="filter-modal-box">
		<div class="filter-search btn-group pull-left">
			<label class="element-invisible" for="filter_search"><?php echo Text::_('JSEARCH_FILTER_LABEL'); ?></label>
			<input type="text" class="hasTooltip" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo $this->escape(Text::_('COM_EXTERNALLOGIN_FILTER_SERVERS_SEARCH_DESC')); ?>" />
		</div>
		<div class="btn-group pull-left">
			<button type="submit" class="btn hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
			<button type="button" class="btn hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
		</div>
	</div>
	<div class="filter-select fltrt">
		<select name="filter_plugin" class="inputbox" onchange="this.form.submit()">
			<option value=""><?php echo Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_PLUGIN'); ?></option>
			<?php echo HTMLHelper::_('select.options', ExternalloginHelper::getPlugins(), 'value', 'text', $this->state->get('filter.plugin'), true); ?>
		</select>
		<select name="filter_published" class="inputbox" onchange="this.form.submit()">
			<option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
			<?php echo HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true); ?>
		</select>
	</div>
</fieldset>
<div class="clr"></div>