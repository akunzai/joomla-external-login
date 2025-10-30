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

namespace Joomla\Module\ExternalloginAdmin\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Externallogin\Administrator\Model\ServersModel;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

/**
 * Helper providing data for the administrator module.
 *
 * @since 5.0.0
 */
class ExternalloginAdminHelper
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
        $app = Factory::getApplication();
        $uri = Uri::getInstance();

        /** @var MVCFactoryServiceInterface $component */
        $component = $app->bootComponent('com_externallogin');
        $mvcFactory = $component->getMVCFactory();

        /** @var ServersModel $model */
        $model = $mvcFactory->createModel('Servers', 'Administrator', ['ignore_request' => true]);
        $model->setState('filter.published', 1);
        $model->setState('filter.enabled', 1);
        $model->setState('filter.servers', $params->get('server'));
        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);
        $model->setState('list.ordering', 'a.ordering');
        $model->setState('list.direction', 'ASC');

        $items = $model->getItems();

        foreach ($items as $i => $item) {
            $item->params = new Registry($item->params);

            $uri->setVar('server', $item->id);

            $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
            $event = new ContentPrepareEvent(
                'onGetLoginUrl',
                [
                    'context' => 'com_externallogin',
                    'subject' => $item,
                ]
            );
            $event->setArgument('service', Route::_($uri, true));
            $dispatcher->dispatch('onGetLoginUrl', $event);

            $results = $event->getArgument('result', []);

            if (!empty($results)) {
                $result = is_array($results) ? $results[0] : $results;
                $item->url = $result;
            } else {
                unset($items[$i]);
            }
        }

        return array_values($items);
    }
}
