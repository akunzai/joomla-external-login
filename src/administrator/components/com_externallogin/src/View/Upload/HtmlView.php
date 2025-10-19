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

namespace Joomla\Component\Externallogin\Administrator\View\Upload;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Component\Externallogin\Administrator\Model\ServerModel;
use RuntimeException;
use Throwable;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * Upload View of External Login component.
 *
 * @property object $state The model state
 * @property object $item The active item
 * @property Form $form The Form object
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
     * The active item.
     *
     * @var object
     */
    protected $item;

    /**
     * The Form object.
     *
     * @var Form
     */
    protected $form;

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
            $form = $model->getForm();

            if ($form === false) {
                throw new RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $state = $model->getState();
            $item = $model->getItem();

            if ($item === false) {
                throw new RuntimeException(Text::_('JERROR_AN_ERROR_HAS_OCCURRED'));
            }

            $this->form = $form;
            $this->state = $state;
            $this->item = $item;
        } catch (Throwable $exception) {
            Log::add($exception->getMessage(), Log::ERROR, 'externallogin');
            $app->enqueueMessage($exception->getMessage(), 'error');
            $app->redirect('index.php', 302);
            return;
        }

        parent::display($tpl);
    }
}
