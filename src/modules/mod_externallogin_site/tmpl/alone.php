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

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

// No direct access to this file
defined('_JEXEC') or die;

/** @var Registry $params */
/** @var array<int, object> $servers */
$params ??= new Registry();
$servers ??= [];

if ($servers === []) {
    return;
}
?>
<?php if ($params->get('show_title', 0)) : ?>
	<h4><?php echo $servers[0]->title; ?></h4>
<?php endif; ?>
<input type="submit" class="btn btn-primary" onclick="document.location.href='<?php echo $servers[0]->url; ?>';return false;" class="button" value="<?php echo htmlspecialchars(Text::_('JLOGIN'), ENT_COMPAT, 'UTF-8'); ?>" />