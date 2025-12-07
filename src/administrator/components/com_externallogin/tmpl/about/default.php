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

use Joomla\CMS\Language\Text;

/** @var \Joomla\Component\Externallogin\Administrator\View\About\HtmlView $this */
?>
<div id="j-sidebar-container" class="span2"><?php echo $this->sidebar; ?></div>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h2><?php echo Text::_('COM_EXTERNALLOGIN'); ?></h2>
                    <p class="lead"><?php echo Text::sprintf('COM_EXTERNALLOGIN_ABOUT_VERSION', $this->version); ?></p>
                    <p>
                        <a href="https://github.com/akunzai/joomla-external-login" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                            <span class="icon-github" aria-hidden="true"></span>
                            GitHub
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
