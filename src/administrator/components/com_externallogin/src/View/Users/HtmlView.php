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

namespace Joomla\Component\Externallogin\Administrator\View\Users;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Externallogin\Administrator\Helper\ExternalloginHelper;
use Joomla\Component\Externallogin\Administrator\Model\UsersModel;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Users View of External Login component.
 *
 * @property object $state The model state
 * @property array $items An array of items
 * @property Pagination $pagination The pagination object
 * @property string $sidebar The HTML for displaying sidebar
 *
 * @since       2.1.0
 */
class HtmlView extends BaseHtmlView
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
     * @var Pagination
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
     * @since   2.1.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        /** @var UsersModel */
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
        /** @var CMSApplication */
        $app = Factory::getApplication();
        /** @var HtmlDocument */
        $document = $app->getDocument();
        $document->getWebAssetManager()
            ->registerAndUseStyle('com_externallogin', 'com_externallogin/administrator/externallogin.css', [], [], []);

        $toolbar = $document->getToolbar();

        // Set the toolbar
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_USERS'), 'users');

        $toolbar->confirmButton('users-enable-joomla', 'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_JOOMLA', 'users.enableJoomla')
            ->message('COM_EXTERNALLOGIN_TOOLBAR_ENABLE_JOOMLA_MSG')
            ->icon('icon-publish')
            ->listCheck(true);

        $toolbar->confirmButton('users-disable-joomla', 'COM_EXTERNALLOGIN_TOOLBAR_DISABLE_JOOMLA', 'users.disableJoomla')
            ->message('COM_EXTERNALLOGIN_TOOLBAR_DISABLE_JOOMLA_MSG')
            ->icon('icon-unpublish')
            ->listCheck(true);

        $toolbar->popupButton('users-enable-externallogin', 'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_EXTERNALLOGIN')
            ->popupType('iframe')
            ->url('index.php?option=com_externallogin&view=servers&layout=modal&tmpl=component')
            ->modalWidth('800px')
            ->modalHeight('300px')
            ->icon('icon-publish');

        ToolbarHelper::custom(
            'users.disableExternallogin',
            'unpublish',
            'users-disable-externallogin',
            'COM_EXTERNALLOGIN_TOOLBAR_DISABLE_EXTERNALLOGIN'
        );
        ToolbarHelper::preferences('com_externallogin');

        $toolbar->popupButton('users-toggle-global', 'COM_EXTERNALLOGIN_TOOLBAR_ENABLE_DISABLE_GLOBAL')
            ->popupType('iframe')
            ->url('index.php?option=com_externallogin&view=servers&layout=modal&tmpl=component&globalS=1')
            ->modalWidth('875px')
            ->modalHeight('300px')
            ->icon('icon-edit');

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
    public function getSortFields()
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
