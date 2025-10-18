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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Toolbar\ToolbarHelper;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Users View of External Login component.
 *
 * @since       2.1.0
 */
class ExternalloginViewUsers extends Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The model state.
     *
     * @var object
     */
    protected $state;

    /**
     * An array of items.
     *
     * @var array
     */
    protected $items;

    /**
     * The pagination object.
     *
     * @var Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

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
     * @see     Overload JViewLegacy::display
     * @since   2.1.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        /** @var ExternalloginModelUsers $model */
        $model = $this->getModel();
        $items = $model->getItems();
        $pagination = $model->getPagination();
        $state = $model->getState();

        // Assign data to the view
        $this->items = $items;
        $this->pagination = $pagination;
        $this->state = $state;

        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'), 'index.php?option=com_externallogin', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'), 'index.php?option=com_externallogin&view=users', true);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'), 'index.php?option=com_externallogin&view=logs', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'), 'index.php?option=com_externallogin&view=about', false);

        // Set the toolbar
        $this->addToolBar();

        $this->sidebar = HTMLHelper::_('sidebar.render');

        // Display the template
        parent::display($tpl);
    }

    /**
     * Setting the toolbar.
     *
     * @since   2.1.0
     */
    protected function addToolbar()
    {
        // Load specific css component
        $app = Factory::getApplication();
        $app->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_externallogin', 'com_externallogin/administrator/externallogin.css', [], [], []);

        $bar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');

        // Set the toolbar
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_USERS'), 'users');

        // Add a standard button.
        $bar->appendButton(
            'Confirm',
            'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_JOOMLA_MSG',
            'publish',
            'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_JOOMLA',
            'users.enableJoomla',
            true
        );
        $bar->appendButton(
            'Confirm',
            'COM_EXTERNALLOGIN_TOOLBAR_DISABLE_JOOMLA_MSG',
            'unpublish',
            'COM_EXTERNALLOGIN_TOOLBAR_DISABLE_JOOMLA',
            'users.disableJoomla',
            true
        );
        $bar->appendButton(
            'Popup',
            'publish',
            'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_EXTERNALLOGIN',
            'index.php?option=com_externallogin&amp;view=servers&amp;layout=modal&amp;tmpl=component',
            800,
            300,
            0,
            0,
            ''
        );
        ToolbarHelper::custom(
            'users.disableExternallogin',
            'unpublish',
            'users-disable-externallogin',
            'COM_EXTERNALLOGIN_TOOLBAR_DISABLE_EXTERNALLOGIN'
        );
        ToolbarHelper::preferences('com_externallogin');
        ToolbarHelper::divider();
        ToolbarHelper::help('COM_EXTERNALLOGIN_HELP_MANAGER_USERS');
        $bar->appendButton(
            'Popup',
            'edit',
            'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_DISABLE_GLOBAL',
            'index.php?option=com_externallogin&amp;view=servers&amp;layout=modal&amp;tmpl=component&globalS=1',
            875,
            300,
            0,
            0,
            ''
        );

        HTMLHelper::_('sidebar.setaction', 'index.php?option=com_externallogin&view=users');

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_PLUGIN'),
            'filter_plugin',
            HTMLHelper::_('select.options', ExternalloginHelper::getPlugins(), 'value', 'text', $this->state->get('filter.plugin'), true)
        );

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_SERVER'),
            'filter_server',
            HTMLHelper::_(
                'select.options',
                ExternalloginHelper::getServers(['ignore_request' => true]),
                'value',
                'text',
                $this->state->get('filter.server'),
                true
            )
        );

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_JOOMLA'),
            'filter_joomla',
            HTMLHelper::_(
                'select.options',
                HTMLHelper::_('jgrid.publishedOptions', ['archived' => false, 'trash' => false, 'all' => false]),
                'value',
                'text',
                $this->state->get('filter.joomla'),
                true
            )
        );

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_EXTERNAL'),
            'filter_external',
            HTMLHelper::_(
                'select.options',
                HTMLHelper::_('jgrid.publishedOptions', ['archived' => false, 'trash' => false, 'all' => false]),
                'value',
                'text',
                $this->state->get('filter.external'),
                true
            )
        );
    }

    /**
     * Returns an array of fields the table can be sorted by.
     *
     * @return array Array containing the field name to sort by as the key and display text as value
     *
     * @since   3.0
     */
    protected function getSortFields()
    {
        return [
            'a.username' => Text::_('COM_EXTERNALLOGIN_HEADING_USERNAME'),
            'a.name' => Text::_('COM_EXTERNALLOGIN_HEADING_NAME'),
            'a.email' => Text::_('COM_EXTERNALLOGIN_HEADING_EMAIL'),
            'a.plugin' => Text::_('COM_EXTERNALLOGIN_HEADING_PLUGIN'),
            's.title' => Text::_('COM_EXTERNALLOGIN_HEADING_SERVER'),
            'a.id' => Text::_('JGRID_HEADING_ID'),
        ];
    }
}
