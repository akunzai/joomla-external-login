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

namespace Joomla\Component\Externallogin\Administrator\View\Servers;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Externallogin\Administrator\Helper\ExternalloginHelper;
use Joomla\Component\Externallogin\Administrator\Model\ServersModel;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Servers View of External Login component.
 *
 * @property object $state The model state
 * @property array $items An array of items
 * @property Pagination $pagination The pagination object
 * @property string $sidebar The HTML for displaying sidebar
 * @property bool $globalS Is a global server?
 *
 * @since       2.0.0
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
     * is a global server?
     *
     * @var bool
     */
    protected $globalS = false;

    /**
     * Execute and display a layout script.
     *
     * @param string $tpl the name of the layout file to parse
     *
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        /** @var ServersModel */
        $model = $this->getModel();
        $items = $model->getItems();
        $pagination = $model->getPagination();
        $state = $model->getState();

        // Get global var if set
        $global = Factory::getApplication()->getInput()->getInt('globalS');

        // Assign data to the view
        $this->items = $items;
        $this->pagination = $pagination;
        $this->state = $state;

        // Check if a server should be set global
        if ($global === 1) {
            $this->globalS = true;
        }

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
        // Load specific css component
        /** @var CMSApplication */
        $app = Factory::getApplication();
        /** @var HtmlDocument */
        $document = $app->getDocument();
        $document->getWebAssetManager()
            ->registerAndUseStyle('com_externallogin', 'com_externallogin/administrator/externallogin.css', [], [], []);

        // Set the toolbar
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_SERVERS'), 'database');

        $toolbar = $document->getToolbar();
        $toolbar->popupButton('servers-new')
            ->popupType('iframe')
            ->url('index.php?option=com_externallogin&view=plugins&tmpl=component')
            ->modalWidth('800px')
            ->modalHeight('400px')
            ->text(Text::_('JTOOLBAR_NEW'))
            ->icon('icon-new');

        ToolbarHelper::editList('server.edit');
        ToolbarHelper::publishList('servers.publish');
        ToolbarHelper::unpublishList('servers.unpublish');
        ToolbarHelper::checkin('servers.checkin');

        if ($this->state->get('filter.published') == -2) {
            ToolbarHelper::deleteList('COM_EXTERNALLOGIN_MSG_SERVERS_DELETE', 'servers.delete');
        } else {
            ToolbarHelper::archiveList('servers.archive');
            ToolbarHelper::trash('servers.trash');
            ToolbarHelper::divider();
        }

        ToolbarHelper::custom('server.upload', 'upload', 'upload', 'COM_EXTERNALLOGIN_TOOLBAR_SERVER_UPLOAD');
        ToolbarHelper::custom('server.download', 'download', 'download', 'COM_EXTERNALLOGIN_TOOLBAR_SERVER_DOWNLOAD');
        ToolbarHelper::preferences('com_externallogin');

        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'), 'index.php?option=com_externallogin', true);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'), 'index.php?option=com_externallogin&view=users', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'), 'index.php?option=com_externallogin&view=logs', false);
        HTMLHelper::_('sidebar.addentry', Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'), 'index.php?option=com_externallogin&view=about', false);

        HTMLHelper::_('sidebar.setaction', 'index.php?option=com_externallogin&view=servers');

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('COM_EXTERNALLOGIN_OPTION_SELECT_PLUGIN'),
            'filter_plugin',
            HTMLHelper::_('select.options', ExternalloginHelper::getPlugins(), 'value', 'text', $this->state->get('filter.plugin'), true)
        );

        HTMLHelper::_(
            'sidebar.addFilter',
            Text::_('JOPTION_SELECT_PUBLISHED'),
            'filter_published',
            HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
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
            'a.title' => Text::_('JGLOBAL_TITLE'),
            'e.ordering' => Text::_('COM_EXTERNALLOGIN_HEADING_PLUGIN'),
            'a.published' => Text::_('JSTATUS'),
            'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
            'a.id' => Text::_('JGRID_HEADING_ID'),
        ];
    }
}
