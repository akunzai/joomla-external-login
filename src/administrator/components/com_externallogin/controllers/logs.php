<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2018 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://github.com/akunzai/joomla-external-login
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Logs Controller of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.1.0
 */
class ExternalloginControllerLogs extends \Joomla\CMS\MVC\Controller\AdminController
{
    /**
     * The prefix to use with controller messages.
     *
     * @var    string
     * @since  2.1.0
     */
    protected $text_prefix = 'COM_EXTERNALLOGIN_LOGS';

    /**
     * Proxy for getModel.
     *
     * @param   string      $name    Model name
     * @param   string      $prefix  Model prefix
     * @param   array|null  $config  Array of options
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @see     JControllerAdmin::getModel
     *
     * @since   2.1.0
     */
    public function getModel($name = 'Log', $prefix = 'ExternalloginModel', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }

    /**
     * Delete logs
     *
     * @return  void
     *
     * @since   2.1.0
     */
    public function delete()
    {
        // Check for request forgeries.
        Session::checkToken() or exit(Text::_('JINVALID_TOKEN'));

        // Get/Create the model
        /** @var ExternalloginModelLogs */
        $model = $this->getModel('Logs', 'ExternalloginModel', []);

        // Remove the items.
        $count = $model->getTotal();

        try {
            $model->delete();
            $this->setMessage(Text::plural('COM_EXTERNALLOGIN_LOGS_N_ITEMS_DELETED', $count));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=logs', false));
    }
}
