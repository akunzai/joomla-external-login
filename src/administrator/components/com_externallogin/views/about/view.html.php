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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * About View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 * @since       2.0.0
 */
class ExternalloginViewAbout extends \Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The HTML for displaying sidebar
     *
     * @var string
     */
    protected $sidebar;

    /**
     * Execute and display a layout script.
     *
     * @param   string  $tpl  The name of the layout file to parse.
     *
     * @return  void|bool
     *
     * @see     Overload JViewLegacy::display
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
     * Setting the toolbar
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function addToolbar()
    {
        // Load specific css component
        HTMLHelper::stylesheet('com_externallogin/administrator/externallogin.css', ['relative' => true]);

        // Set the title
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_ABOUT'), 'help');

        ToolbarHelper::preferences('com_externallogin');
        ToolbarHelper::divider();
        ToolbarHelper::help('COM_EXTERNALLOGIN_HELP_MANAGER_ABOUT');

        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'), 'index.php?option=com_externallogin', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'), 'index.php?option=com_externallogin&view=users', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'), 'index.php?option=com_externallogin&view=logs', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'), 'index.php?option=com_externallogin&view=about', true);
    }
}
