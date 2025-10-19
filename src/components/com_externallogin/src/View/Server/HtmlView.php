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

namespace Joomla\Component\Externallogin\Site\View\Server;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Externallogin\Site\Model\ServerModel;
use Throwable;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Server View of External Login component.
 *
 * @since       2.0.0
 */
class HtmlView extends BaseHtmlView
{
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
            /** @var ServerModel */
            $model = $this->getModel();
            $item = $model->getItem();
            $app->redirect((string) $item, 302);
        } catch (Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'externallogin');
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect('index.php', 302);
            return;
        }

    }
}
