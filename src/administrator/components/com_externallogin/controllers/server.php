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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// No direct access to this file
defined('_JEXEC') or die;

// Import Joomla controllerform library
JLoader::import('joomla.application.component.controllerform');

/**
 * Server Controller of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginControllerServer extends \Joomla\CMS\MVC\Controller\FormController
{
    /**
     * Gets the URL arguments to append to an item redirect.
     *
     * @param   integer  $recordId  The primary key id for the item.
     * @param   string   $urlVar    The name of the URL variable for the id.
     *
     * @return  string  The arguments to append to the redirect URL.
     *
     * @see  JControllerForm::getRedirectToItemAppend
     *
     * @since   2.0.0
     */
    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $plugin = Factory::getApplication()->input->get('plugin', '');
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);

        if (!empty($plugin)) {
            $append .= '&plugin=' . $plugin;
        }

        return $append;
    }

    /**
     * Download users
     *
     * @return  boolean  True on success
     */
    public function download()
    {
        // Check for request forgeries.
        Session::checkToken() or exit(Text::_('JINVALID_TOKEN'));

        // Initialise variables.
        $cid = Factory::getApplication()->input->get('cid', [], 'array');

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=download&format=csv&id=' . $cid[0], false));

        return true;
    }

    /**
     * Upload users
     *
     * @return  void
     */
    public function upload()
    {
        // Check for request forgeries.
        Session::checkToken() or exit(Text::_('JINVALID_TOKEN'));

        // Initialise variables.
        $form = Factory::getApplication()->input->get('jform', [], 'array');
        $id = (int) $form['id'];

        $model = $this->getModel();

        if ($model->upload($form)) {
            $this->setRedirect(
                Route::_('index.php?option=com_externallogin&view=success&tmpl=component', false),
                Text::_('COM_EXTERNALLOGIN_MSG_UPLOAD_SUCCESS')
            );
            return;
        }
        $this->setRedirect(
            Route::_('index.php?option=com_externallogin&view=upload&tmpl=component&id=' . $id, false),
            $model->get('error'),
            'error'
        );
    }
}
