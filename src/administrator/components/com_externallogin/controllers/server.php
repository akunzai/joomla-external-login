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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Server Controller of External Login component.
 *
 * @since       2.0.0
 */
class ExternalloginControllerServer extends Joomla\CMS\MVC\Controller\FormController
{
    /**
     * Gets the URL arguments to append to an item redirect.
     *
     * @param int $recordId the primary key id for the item
     * @param string $urlVar the name of the URL variable for the id
     *
     * @return string the arguments to append to the redirect URL
     *
     * @see  JControllerForm::getRedirectToItemAppend
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
     * Download users.
     *
     * @return bool True on success
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
     * Upload users.
     */
    public function upload()
    {
        // Check for request forgeries.
        Session::checkToken() or exit(Text::_('JINVALID_TOKEN'));

        // Initialise variables.
        $form = Factory::getApplication()->input->get('jform', [], 'array');
        $id = (int) $form['id'];

        $model = $this->getModel();

        try {
            $model->upload($form);
            $this->setRedirect(
                Route::_('index.php?option=com_externallogin&view=success&tmpl=component', false),
                Text::_('COM_EXTERNALLOGIN_MSG_UPLOAD_SUCCESS')
            );
        } catch (Exception $e) {
            $this->setRedirect(
                Route::_('index.php?option=com_externallogin&view=upload&tmpl=component&id=' . $id, false),
                $e->getMessage(),
                'error'
            );
        }
    }
}
