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

namespace Joomla\Component\Externallogin\Site\View\Login;

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Externallogin\Site\Model\LoginModel;
use Joomla\Registry\Registry;
use Throwable;

/**
 * Login View of External Login component.
 *
 * @since       2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The model state.
     *
     * @var object
     */
    protected $state;

    /**
     * An array of items.
     *
     * @var array
     */
    public $items;

    /**
     * The parameter object.
     *
     * @var Registry
     */
    public $params;

    /**
     * The page class suffix.
     *
     * @var string
     */
    public $pageclass_sfx = '';

    /**
     * Execute and display a layout script.
     *
     * @param string $tpl the name of the layout file to parse
     *
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        /** @var CMSApplication */
        $app = Factory::getApplication();

        try {
            /** @var LoginModel */
            $model = $this->getModel();
            $this->items = $model->getItems();
            $this->state = $model->getState();
            $this->params = ComponentHelper::getParams('com_externallogin');
        } catch (Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'externallogin');
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect('index.php', 302);
            return;
        }

        parent::display($tpl);
    }
}
