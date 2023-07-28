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

/**
 * Log View of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.1.0
 */
class ExternalloginViewLogs extends \Joomla\CMS\MVC\View\HtmlView
{
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
        $basename = $this->get('BaseName');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            /** @var \Joomla\CMS\Application\CMSApplication */
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        header('Content-Type: text/csv; charset=utf8');
        header('Content-Disposition: attachment; filename="' . $basename . '".csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $this->get('Content');
        exit();
    }
}
