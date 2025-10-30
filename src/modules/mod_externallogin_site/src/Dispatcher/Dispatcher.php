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

namespace Joomla\Module\ExternalloginSite\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Module\ExternalloginSite\Site\Helper\ExternalloginSiteHelper;
use Joomla\Registry\Registry;

/**
 * Module dispatcher for mod_externallogin_site.
 *
 * @since 5.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Retrieve the layout data.
     *
     * @return array<string, mixed>
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        /** @var Registry $params */
        $params = $data['params'];

        $data['enabled'] = ComponentHelper::getComponent('com_externallogin', true)->enabled
            && PluginHelper::isEnabled('authentication', 'externallogin');

        /** @var ExternalloginSiteHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('ExternalloginSiteHelper');
        $servers = $helper->getServers($params);

        $data['servers'] = $servers;
        $data['count'] = count($servers);
        $data['return'] = $helper->getLogoutReturn($params);
        $data['user'] = $this->getApplication()->getIdentity();

        return $data;
    }
}
