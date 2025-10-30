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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\Externallogin\Administrator\View\About\HtmlView $this */
echo '<div id="j-sidebar-container" class="span2">' . $this->sidebar . '</div>';

echo sprintf(
    Text::_('COM_EXTERNALLOGIN_ABOUT'),
    'http://www.univ-montp2.fr',
    HTMLHelper::_('image', 'com_externallogin/administrator/logo_um2.png', 'logo_um2', null, true)
);
?>
<br>
<h2 style="text-align: center;">...and upgraded to Joomla! 3.x by <a href="http://www.ninjaforge.com/" target="_blank">Ninja Forge</a> and <a href="http://www.pdxfixit.com/" target="_blank">PDXfixIT</a> with the help and the final validation of Christophe Demko.</h2>