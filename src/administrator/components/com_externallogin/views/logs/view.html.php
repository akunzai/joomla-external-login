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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Logs View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.1.0
 */
class ExternalloginViewLogs extends \Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The model state
     *
     * @var \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * An array of items.
     *
     * @var array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The HTML for displaying sidebar
     *
     * @var    string
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
     * @since   2.1.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        $model = $this->getModel();
        $items = $model->getItems();
        $pagination = $model->getPagination();
        $state = $model->getState();

        // Assign data to the view
        $this->items = $items;
        $this->pagination = $pagination;
        $this->state = $state;

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
     * @since   2.1.0
     */
    protected function addToolbar()
    {
        // Load specific css component
        $app = Factory::getApplication();
        $app->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_externallogin', 'com_externallogin/administrator/externallogin.css', [], [], []);

        // Set the toolbar
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_LOGS'), 'list-view');
        $toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');
        $toolbar->appendButton('Confirm', 'COM_EXTERNALLOGIN_MSG_LOGS_DELETE', 'delete', 'JTOOLBAR_DELETE', 'logs.delete', false);
        $toolbar->appendButton('Link', 'download', 'COM_EXTERNALLOGIN_TOOLBAR_LOGS_DOWNLOAD', 'index.php?option=com_externallogin&view=logs&format=csv');
        ToolbarHelper::preferences('com_externallogin');
        ToolbarHelper::divider();
        ToolbarHelper::help('COM_EXTERNALLOGIN_HELP_MANAGER_LOGS');

        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'), 'index.php?option=com_externallogin', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'), 'index.php?option=com_externallogin&view=users', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'), 'index.php?option=com_externallogin&view=logs', true);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'), 'index.php?option=com_externallogin&view=about', false);

        HTMLHelper::_('sidebar.setaction', 'index.php?option=com_externallogin&view=logs');

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_PRIORITY'),
            'filter_priority',
            HTMLHelper::_('select.options', ExternalloginHelper::getPriorities(), 'value', 'text', $this->state->get('filter.priority'), true)
        );

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('JOPTION_SELECT_PUBLISHED'),
            'filter_category',
            HTMLHelper::_('select.options', ExternalloginHelper::getCategories(), 'value', 'text', $this->state->get('filter.category'), true)
        );
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     *
     * @since   3.0
     */
    protected function getSortFields()
    {
        return [
            'a.priority' => Text::_('COM_EXTERNALLOGIN_HEADING_PRIORITY'),
            'a.category' => Text::_('COM_EXTERNALLOGIN_HEADING_CATEGORY'),
            'a.date' => Text::_('COM_EXTERNALLOGIN_HEADING_DATE'),
            'a.message' => Text::_('COM_EXTERNALLOGIN_HEADING_MESSAGE'),
        ];
    }
}
