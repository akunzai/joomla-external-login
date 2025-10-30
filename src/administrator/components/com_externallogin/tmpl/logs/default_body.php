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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\Externallogin\Administrator\View\Logs\HtmlView $this */
$user = Factory::getApplication()->getIdentity();

if (!count($this->items)) {
    ?>
	<tr class="row0">
		<td colspan="4" class="center">
			<?php echo Text::_('COM_EXTERNALLOGIN_NO_RECORDS'); ?>
		</td>
	</tr>
	<?php
} else {
    foreach ($this->items as $i => $item) : ?>
		<tr class="row<?php echo $i % 2; ?>">
			<td>
				<?php echo $this->escape(Text::_('COM_EXTERNALLOGIN_GRID_LOG_PRIORITY_' . $item->priority)); ?>
			</td>
			<td>
				<?php echo $this->escape($item->category); ?>
			</td>
			<td>
				<?php echo date('Y-m-d H:i:s', (int)$item->date); ?>
			</td>
			<td>
				<?php echo $this->escape($item->message); ?>
			</td>
		</tr>
<?php endforeach;
} ?>