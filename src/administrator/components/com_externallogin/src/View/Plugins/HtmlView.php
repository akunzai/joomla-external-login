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

namespace Joomla\Component\Externallogin\Administrator\View\Plugins;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Externallogin\Administrator\Model\PluginsModel;
use Throwable;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Plugins View of External Login component.
 *
 * @property array $items An array of items
 *
 * @since       2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items.
     *
     * @var array
     */
    protected $items;

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
            // Get data from the model
            /** @var PluginsModel */
            $model = $this->getModel();
            $this->items = $model->getItems();
        } catch (Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'externallogin');
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect('index.php', 302);
            return;
        }

        parent::display($tpl);
    }
}
