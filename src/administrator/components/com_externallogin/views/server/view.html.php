<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

// No direct access to this file
defined('_JEXEC') or die;

// Import Joomla view library
JLoader::import('joomla.application.component.view');

/**
 * Server View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginViewServer extends \Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The model state
     *
     * @var object
     */
    protected $state;

    /**
     * The active item
     *
     * @var object
     */
    protected $item;

    /**
     * The Form object
     *
     * @var \Joomla\CMS\Form\Form
     */
    protected $form;

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
        // Get data from the model
        $item = $this->get('Item');
        $form = $this->get('Form');
        $state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            /** @var \Joomla\CMS\Application\CMSApplication */
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        // Assign data to the view
        $this->item = $item;
        $this->form = $form;
        $this->state = $state;

        // Set the toolbar
        $this->addToolBar();

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

        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getUser();
        $userId = $user->get('id');
        $isNew = $this->item->id == 0;
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
        $type = ($checkedOut ? 'view' : $isNew) ? 'new' : 'edit';

        // Set the title
        ToolbarHelper::title(Text::_('COM_EXTERNALLOGIN_MANAGER_SERVER_' . $type), 'server-' . $type);

        // Build the actions for new and existing records.
        if ($isNew) {
            ToolbarHelper::apply('server.apply');
            ToolbarHelper::save('server.save');
            ToolbarHelper::cancel('server.cancel');
        } else {
            // Can't save the record if it's checked out.
            if (!$checkedOut) {
                ToolbarHelper::apply('server.apply');
                ToolbarHelper::save('server.save');
            }
            ToolbarHelper::cancel('server.cancel', 'JTOOLBAR_CLOSE');
        }
        ToolbarHelper::divider();
        ToolbarHelper::help('COM_EXTERNALLOGIN_HELP_MANAGER_SERVER');
    }
}
