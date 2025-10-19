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

namespace Joomla\Component\Externallogin\Administrator\View\About;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * About View of External Login component.
 *
 * @property string $sidebar The HTML for displaying sidebar
 *
 * @since       2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The HTML for displaying sidebar.
     *
     * @var string
     */
    protected $sidebar;

    /**
     * Execute and display a layout script.
     *
     * @param string $tpl the name of the layout file to parse
     *
     * @return void|bool
     *
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        // Set the toolbar
        $this->addToolBar();

        $this->sidebar = HTMLHelper::_('sidebar.render');

        // Display the template
        parent::display($tpl);
    }

    /**
     * Setting the toolbar.
     *
     * @since   2.0.0
     */
    protected function addToolbar()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        // Load specific css component
        $app->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_externallogin', 'com_externallogin/administrator/externallogin.css', [], [], []);

        // Set the title
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_ABOUT'), 'help');

        ToolbarHelper::preferences('com_externallogin');

        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'), 'index.php?option=com_externallogin', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'), 'index.php?option=com_externallogin&view=users', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'), 'index.php?option=com_externallogin&view=logs', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'), 'index.php?option=com_externallogin&view=about', true);
    }
}
