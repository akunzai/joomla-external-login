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

use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Factory;

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
class ExternalloginViewDownload extends \Joomla\CMS\MVC\View\HtmlView
{
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
        $basename = $this->get('BaseName');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        $document = Factory::getDocument();
        $document->setMimeEncoding('text/csv');
        WebApplication::getInstance()->setHeader(
            'Content-disposition',
            'attachment; filename="' . $basename . '.csv"; creation-date="' . Factory::getDate()->toRFC822() . '"',
            true
        );
        $this->get('Content');
    }
}
