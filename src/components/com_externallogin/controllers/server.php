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
 * Server Controller of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.3.0
 */
class ExternalloginControllerServer extends \Joomla\CMS\MVC\Controller\BaseController
{
    /**
     * Proxy for getModel.
     *
     * @param   string      $name    Model name
     * @param   string      $prefix  Model prefix
     * @param   array|null  $config  Options
     *
     * @return  ExternalloginModelServer
     *
     * @see     JControllerLegacy::getModel
     *
     * @since   2.3.0
     */
    public function getModel($name = 'Server', $prefix = 'ExternalloginModel', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }

    /**
     * Login task.
     *
     * @return  void
     *
     * @since   2.3.0
     */
    public function login()
    {
        Session::checkToken('post') or exit(Text::_('JInvalid_Token'));

        // Get the model
        $model = $this->getModel();

        try {
            // Get the uri
            $uri = $model->getItem();
            $this->setRedirect(Route::_($uri, false));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage(), 'warning');
            $this->setRedirect('index.php', false);
        }
    }
}
