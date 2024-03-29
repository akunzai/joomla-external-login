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

use Joomla\CMS\Language\Text;

// No direct access to this file
defined('_JEXEC') or die;
?>
<div class="cpanel">
	<?php if (empty($this->items)) : ?>
		<?php echo Text::_('COM_EXTERNALLOGIN_NO_PLUGINS'); ?>
	<?php else : ?>
		<?php foreach ($this->items as $item) : ?>
			<a class="btn" href="<?php echo $item['link'] ?>" target="<?php echo $item['target']; ?>">
				<span class="<?php echo $item['image']; ?>" title="<?php echo htmlspecialchars($item['alt'], ENT_COMPAT, 'UTF-8'); ?>"></span>
				<br />
				<span><?php echo $item['text']; ?></span>
			</a>
		<?php endforeach; ?>
	<?php endif; ?>
</div>