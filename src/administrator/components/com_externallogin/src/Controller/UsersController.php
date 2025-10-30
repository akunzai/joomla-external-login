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

namespace Joomla\Component\Externallogin\Administrator\Controller;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Externallogin\Administrator\Model\UserModel;
use Joomla\Utilities\ArrayHelper;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Users Controller of External Login component.
 *
 * @since       2.1.0
 */
class UsersController extends BaseController
{
    /**
     * Proxy for getModel.
     *
     * @param string $name Model name
     * @param string $prefix Model prefix
     * @param array|null $config Array of options
     *
     * @return BaseDatabaseModel|false
     *
     * @since   2.1.0
     */
    public function getModel($name = 'User', $prefix = '', $config = null)
    {
        return parent::getModel($name, $prefix, $config ?? ['ignore_request' => true]);
    }

    /**
     * Enable external login users to login using classical Joomla! method.
     *
     * @since   2.1.0
     */
    public function enableJoomla()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Get items to publish from the request.
        $cid = Factory::getApplication()->getInput()->get('cid', [], 'array');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            /** @var UserModel $model */
            $model = $this->getModel();

            if ($model) {
                // Make sure the item ids are integers
                ArrayHelper::toInteger($cid);

                // Publish the items.
                try {
                    if ($model->enableJoomla($cid)) {
                        $this->setMessage(Text::plural('COM_EXTERNALLOGIN_USERS_N_USERS_JOOMLA_ENABLED', count($cid)));
                    }
                } catch (Exception $e) {
                    $this->setMessage($e->getMessage(), 'error');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }

    /**
     * Enable external login users to login using classical Joomla! method.
     *
     * @since   2.1.0
     */
    public function disableJoomla()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Get items to publish from the request.
        $cid = Factory::getApplication()->getInput()->get('cid', [], 'array');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            /** @var UserModel $model */
            $model = $this->getModel();

            if ($model) {
                // Make sure the item ids are integers
                ArrayHelper::toInteger($cid);

                // Publish the items.
                try {
                    if ($model->disableJoomla($cid)) {
                        $this->setMessage(Text::plural('COM_EXTERNALLOGIN_USERS_N_USERS_JOOMLA_DISABLED', count($cid)));
                    }
                } catch (Exception $e) {
                    $this->setMessage($e->getMessage(), 'error');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }

    /**
     * Disable Joomla! users to login using external login method.
     *
     * @since   2.1.0
     */
    public function disableExternallogin()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Get items to publish from the request.
        $cid = Factory::getApplication()->getInput()->get('cid', [], 'array');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            /** @var UserModel $model */
            $model = $this->getModel();

            if ($model) {
                // Make sure the item ids are integers
                ArrayHelper::toInteger($cid);

                // Publish the items.
                try {
                    if ($model->disableExternallogin($cid)) {
                        $this->setMessage(Text::plural('COM_EXTERNALLOGIN_USERS_N_USERS_EXTERNALLOGIN_DISABLED', count($cid)));
                    }
                } catch (Exception $e) {
                    $this->setMessage($e->getMessage(), 'error');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }

    /**
     * Disable all Joomla! users to login using external login method for the selected server.
     *
     * @since   2.1.0
     */
    public function disableExternalloginGlobal()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        // Get server id.
        $sid = Factory::getApplication()->getInput()->getInt('server');

        // Get the model.
        /** @var UserModel $model */
        $model = $this->getModel();

        if ($model) {
            // Publish the items.
            $success = $model->disableExternalloginGlobal($sid);
        } else {
            $success = false;
        }

        // Check if disable was successful
        if ($success) {
            $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_ALL_USERS_EXTERNALLOGIN_DISABLED'));
        }

        // Go back to user overview
        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }

    /**
     * Enable Joomla! users to login using external login method.
     *
     * @since   2.1.0
     */
    public function enableExternallogin()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
        $input = Factory::getApplication()->getInput();

        // Get items to publish from the request.
        $cid  = $input->get('cid', [], 'array');
        $sid  = $input->get('server', 0, 'uint');

        if (empty($cid)) {
            $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_NO_ITEM_SELECTED'), 'warning');
        } else {
            // Get the model.
            /** @var UserModel $model */
            $model = $this->getModel();

            if ($model) {
                // Make sure the item ids are integers
                ArrayHelper::toInteger($cid);

                // Publish the items.
                try {
                    if ($model->enableExternallogin($cid, $sid)) {
                        $this->setMessage(Text::plural('COM_EXTERNALLOGIN_USERS_N_USERS_EXTERNALLOGIN_ENABLED', count($cid)));
                    }
                } catch (Exception $e) {
                    $this->setMessage($e->getMessage(), 'error');
                }
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }

    /**
     * Enable all Joomla! users to login using selected external login method.
     *
     * @since   2.1.1
     */
    public function enableExternalloginGlobal()
    {
        // Check for request forgeries
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
        $app = Factory::getApplication();

        // Get server id.
        $sid = $app->getInput()->getInt('server');

        // Get the model.
        /** @var UserModel $model */
        $model = $this->getModel();

        if ($model) {
            // Publish the items.
            try {
                // Check if enable was successful
                if ($model->enableExternalloginGlobal($sid)) {
                    $this->setMessage(Text::_('COM_EXTERNALLOGIN_USERS_ALL_USERS_JOOMLA_ENABLED'));
                }
            } catch (Exception $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
        }

        // Go back to user overview
        $this->setRedirect(Route::_('index.php?option=com_externallogin&view=users', false));
    }
}
