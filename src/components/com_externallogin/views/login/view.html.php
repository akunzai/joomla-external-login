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
use Joomla\CMS\Component\ComponentHelper;

// No direct access to this file
defined('_JEXEC') or die;

// Import Joomla view library
JLoader::import('joomla.application.component.view');
JLoader::import('joomla.application.component.helper');

/**
 * Login View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginViewLogin extends \Joomla\CMS\MVC\View\HtmlView
{
    /**
     * The model state
     *
     * @var object
     */
    protected $state;

    /**
     * An array of items
     *
     * @var array
     */
    protected $items;

    /**
     * The parameter object
     *
     * @var \Joomla\Registry\Registry
     */
    protected $params;

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
        $items = $this->get('Items');
        $state = $this->get('State');
        $params = ComponentHelper::getParams('com_externallogin');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        // Assign data to the view
        $this->items = $items;
        $this->state = $state;
        $this->params = $params;

        // Display the template
        parent::display($tpl);
    }
}
