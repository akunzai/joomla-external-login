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

namespace Joomla\Module\ExternalloginSite\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Externallogin\Administrator\Model\ServersModel;
use Joomla\Registry\Registry;

/**
 * Helper providing data for the site module.
 *
 * @since 5.0.0
 */
class ExternalloginSiteHelper
{
    /**
     * Retrieve enabled external login servers for the module.
     *
     * @param Registry $params Module parameters
     *
     * @return array<int, object>
     */
    public function getServers(Registry $params): array
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $redirect = $app->getInput()->get('redirect', $app->getUserState('users.login.form.data.return'));

        $redirect = $redirect ? urlencode($redirect) : $params->get('redirect');

        $isHome = in_array(substr((string) Uri::getInstance(), strlen(Uri::base())), ['', 'index.php']);
        $noRedirect = $params->get('noredirect');

        /** @var MVCFactoryServiceInterface $component */
        $component = $app->bootComponent('com_externallogin');
        $mvcFactory = $component->getMVCFactory();

        /** @var ServersModel $model */
        $model = $mvcFactory->createModel('Servers', 'Administrator', ['ignore_request' => true]);

        if (!$model) {
            return [];
        }

        $model->setState('filter.published', 1);
        $model->setState('filter.enabled', 1);
        $model->setState('filter.servers', $params->get('server'));
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);
        $model->setState('list.ordering', 'a.ordering');
        $model->setState('list.direction', 'ASC');

        $items = $model->getItems();

        foreach ($items as $item) {
            $item->params = new Registry($item->params);
            $url = 'index.php?option=com_externallogin&view=server&server=' . $item->id;

            if ($noRedirect && !$isHome) {
                $url .= '&noredirect=1';
            } elseif (!empty($redirect)) {
                $url .= '&redirect=' . $redirect;
            }

            $item->url = $url;
        }

        return $items;
    }

    /**
     * Retrieve the url where the user should be returned after logging out.
     *
     * @param Registry $params Module parameters
     */
    public function getLogoutReturn(Registry $params): string
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $item = $app->getMenu()->getItem(
            $params->get(
                'logout_redirect_menuitem',
                ComponentHelper::getParams('com_externallogin')->get('logout_redirect_menuitem')
            )
        );

        $url = Uri::getInstance()->toString();

        if ($item) {
            $lang = '';

            if (Multilanguage::isEnabled() && $item->language !== '*') {
                $lang = '&lang=' . $item->language;
            }

            $url = Route::_('index.php?Itemid=' . $item->id . $lang, false, $app->get('force_ssl') === 2 ? 1 : 0);
        }

        return base64_encode($url);
    }
}
