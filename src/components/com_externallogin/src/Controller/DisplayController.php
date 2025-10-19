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

use Joomla\CMS\MVC\Controller\BaseController;

// No direct access to this file
defined('_JEXEC') or die;

/**
 * General Controller of External Login component.
 *
 * @since       2.0.0
 */
class DisplayController extends BaseController
{
    /**
     * @var string the default view for the display method
     *
     * @since  0.0.1
     */
    protected $default_view = 'login';
}
