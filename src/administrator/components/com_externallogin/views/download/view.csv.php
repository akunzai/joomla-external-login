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

use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Factory;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Server View of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginViewDownload extends Joomla\CMS\MVC\View\HtmlView
{
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
        $model = $this->getModel();
        $basename = $model->getBaseName();

        // Check for errors.
        if (count($errors = $model->getErrors())) {
            /** @var Joomla\CMS\Application\CMSApplication */
            $app = Factory::getApplication();
            $app->enqueueMessage(implode('<br />', $errors), 'error');
            $app->redirect('index.php');

            return false;
        }

        header('Content-Type: text/csv; charset=utf8');
        header('Content-Disposition: attachment; filename="' . $basename . '".csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        $model->getContent();
        exit();
    }
}
