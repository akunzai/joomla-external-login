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

// No direct access to this file
defined('_JEXEC') or die;

// Import Joomla view library
JLoader::import('joomla.application.component.view');

/**
 * Upload View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginViewUpload extends \Joomla\CMS\MVC\View\HtmlView
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
     * @return  void|JError
     *
     * @see     Overload JView::display
     *
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        $form = $this->get('Form');
        $state = $this->get('State');
        $item = $this->get('Item');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        // Assign data to the view
        $this->form = $form;
        $this->state = $state;
        $this->item = $item;

        // Display the template
        parent::display($tpl);
    }
}
