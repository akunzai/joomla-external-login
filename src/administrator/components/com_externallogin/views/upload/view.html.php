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

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Upload View of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginViewUpload extends Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The model state.
     *
     * @var object
     */
    protected $state;

    /**
     * The active item.
     *
     * @var object
     */
    protected $item;

    /**
     * The Form object.
     *
     * @var Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * Execute and display a layout script.
     *
     * @param string $tpl the name of the layout file to parse
     *
     * @return void|bool
     *
     * @see     Overload JViewLegacy::display
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        /** @var ExternalloginModelServer $model */
        $model = $this->getModel();
        $form = $model->getForm();
        $state = $model->getState();
        $item = $model->getItem();

        // Check for errors.
        if (count($errors = $model->getErrors())) {
            /** @var Joomla\CMS\Application\CMSApplication */
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
