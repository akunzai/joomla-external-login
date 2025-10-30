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

namespace Joomla\Component\Externallogin\Site\Controller;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Externallogin\Site\Model\ServerModel;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Server Controller of External Login component.
 *
 * @since       2.3.0
 */
class ServerController extends BaseController
{
    /**
     * Proxy for getModel.
     *
     * @param string $name Model name
     * @param string $prefix Model prefix
     * @param array|null $config Options
     *
     * @return ServerModel|bool
     *
     * @since   2.3.0
     */
    public function getModel($name = 'Server', $prefix = '', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }

    /**
     * Login task.
     *
     * @since   2.3.0
     */
    public function login()
    {
        Session::checkToken('post') or exit(Text::_('JINVALID_TOKEN'));

        // Get the model
        $model = $this->getModel();

        /** @var CMSApplication */
        $app = Factory::getApplication();

        try {
            // Get the uri
            $uri = $model->getItem();
            $app->redirect(Route::_((string) $uri, false), 302);
        } catch (Exception $e) {
            Log::add($e->getMessage(), Log::ERROR, 'externallogin');
            $this->setMessage($e->getMessage(), 'warning');
            $app->redirect('index.php', 302);
        }
    }
}
