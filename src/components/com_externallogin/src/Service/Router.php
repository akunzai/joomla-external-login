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

namespace Joomla\Component\Externallogin\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Routing class for com_externallogin.
 *
 * @since 5.0.0
 */
class Router extends RouterView
{
    /**
     * Constructor.
     *
     * @param CMSApplication|null $app The application object
     * @param AbstractMenu $menu The menu object to work with
     *
     * @since 5.0.0
     */
    public function __construct(?CMSApplication $app, AbstractMenu $menu)
    {
        // Register the login view
        $login = new RouterViewConfiguration('login');
        $this->registerView($login);

        // Register the server view used for external redirects
        $server = new RouterViewConfiguration('server');
        $server->setKey('server');
        $this->registerView($server);

        parent::__construct($app, $menu);

        // Add standard routing rules
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
    }
}
