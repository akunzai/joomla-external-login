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

namespace Joomla\Component\Externallogin\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Menu\SiteMenu;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Router\Route;
use Joomla\Component\Externallogin\Administrator\Model\ServersModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

/**
 * External Login component helper.
 *
 * Static helper methods for the External Login component.
 *
 * @since 5.0.0
 */
class ExternalloginHelper
{
    /**
     * Configure the Linkbar.
     *
     * @param string $submenu The name of the current submenu
     *
     * @since  5.0.0
     */
    public static function addSubmenu(string $submenu = 'servers'): void
    {
        // Add submenu entries
        Sidebar::addEntry(
            Text::_('COM_EXTERNALLOGIN_SUBMENU_SERVERS'),
            Route::_('index.php?option=com_externallogin', false),
            $submenu === 'servers'
        );
        Sidebar::addEntry(
            Text::_('COM_EXTERNALLOGIN_SUBMENU_USERS'),
            Route::_('index.php?option=com_externallogin&view=users', false),
            $submenu === 'users'
        );
        Sidebar::addEntry(
            Text::_('COM_EXTERNALLOGIN_SUBMENU_LOGS'),
            Route::_('index.php?option=com_externallogin&view=logs', false),
            $submenu === 'logs'
        );
        Sidebar::addEntry(
            Text::_('COM_EXTERNALLOGIN_SUBMENU_ABOUT'),
            Route::_('index.php?option=com_externallogin&view=about', false),
            $submenu === 'about'
        );

        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $document = $app->getDocument();
        // Set document title
        $document->setTitle(
            sprintf(
                Text::_('COM_EXTERNALLOGIN_PAGETITLE'),
                $app->get('sitename'),
                Text::_('COM_EXTERNALLOGIN_PAGETITLE_' . $submenu)
            )
        );
    }

    /**
     * Get a list of enabled plugins.
     *
     * @return array Array of plugins
     *
     * @since  5.0.0
     */
    public static function getPlugins(): array
    {
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);

        $event = new ContentPrepareEvent(
            'onGetOption',
            [
                'context' => 'com_externallogin',
                'subject' => new \stdClass(),
            ]
        );

        $dispatcher->dispatch('onGetOption', $event);

        return (array) $event->getArgument('result', []);
    }

    /**
     * Get a list of servers.
     *
     * @param array $config Array of options
     *
     * @return array Array of servers
     *
     * @since  5.0.0
     */
    public static function getServers(array $config = []): array
    {
        $app = Factory::getApplication();

        /** @var MVCFactoryServiceInterface $component */
        $component = $app->bootComponent('com_externallogin');
        $mvcFactory = $component->getMVCFactory();

        /** @var ServersModel $model */
        $model = $mvcFactory->createModel('Servers', 'Administrator', $config);

        if (!$model) {
            return [];
        }

        $model->setState('list.ordering', 'a.ordering');
        $model->setState('list.direction', 'ASC');
        $items = $model->getItems();

        $options = [];
        foreach ($items as $item) {
            $options[] = ['value' => $item->id, 'text' => $item->title];
        }

        return $options;
    }

    /**
     * Get a list of priorities.
     *
     * @return array Array of priorities
     *
     * @since  5.0.0
     */
    public static function getPriorities(): array
    {
        $options = [];

        for ($i = 1; $i <= 128; $i *= 2) {
            $options[] = ['value' => $i, 'text' => 'COM_EXTERNALLOGIN_GRID_LOG_PRIORITY_' . $i];
        }

        return $options;
    }

    /**
     * Get a list of categories.
     *
     * @return array Array of categories
     *
     * @since  5.0.0
     */
    public static function getCategories(): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $categories = $db->setQuery(
            $db->getQuery(true)
                ->select('category')
                ->from('#__externallogin_logs')
                ->group('category')
        )->loadColumn();

        $options = [];
        foreach ($categories as $category) {
            $options[] = ['value' => $category, 'text' => $category];
        }

        return $options;
    }

    /**
     * Get a list of groups from a string.
     *
     * @param string $path Group path
     * @param string $separator Separator string
     *
     * @return array Array of groups
     *
     * @since  5.0.0
     */
    public static function getGroups(string $path, string $separator = '/'): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Split the path
        $path = empty($separator) ? [$path] : explode($separator, $path);

        $count = count($path);

        // Path is incorrect (last element must not be empty)
        if (empty($path[$count - 1])) {
            return [];
        }

        // Prepare query
        $query = $db->getQuery(true);
        $query->select('a' . ($count - 1) . '.id as id');
        $query->from('#__usergroups AS a' . ($count - 1));
        $query->where('a' . ($count - 1) . '.title = ' . $db->quote($path[$count - 1]));

        for ($i = $count - 2; $i >= 0; $i--) {
            if (empty($path[$i])) {
                if ($i === 0) {
                    // Path is absolute
                    $query->where('a1.parent_id = 0');
                } else {
                    // Path is incorrect
                    return [];
                }
            } else {
                $query->leftJoin('#__usergroups AS a' . $i . ' ON a' . $i . '.id = a' . ($i + 1) . '.parent_id');
                $query->where('a' . $i . '.title LIKE ' . $db->quote($path[$i]));
            }
        }

        $db->setQuery($query);

        return $db->loadColumn();
    }

    /**
     * Compute a redirect URL.
     *
     * @param string|int $redirect A menu item id or an urlencoded url
     *
     * @return string The url from the $redirect parameter
     *
     * @since  5.0.0
     */
    public static function url(string|int $redirect): string
    {
        if (!is_numeric($redirect)) {
            return urldecode((string) $redirect);
        }

        // Get site menu
        $menu = Factory::getContainer()->get(SiteMenu::class);
        $item = $menu->getItem((int) $redirect);

        if ($item) {
            switch ($item->type) {
                case 'url':
                    if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false)) {
                        // If this is an internal Joomla link, ensure the Itemid is set.
                        $link = $item->link . '&Itemid=' . $item->id;
                    } else {
                        $link = $item->link;
                    }
                    break;

                case 'alias':
                    $link = 'index.php?Itemid=' . $item->getParams()->get('aliasoptions');
                    break;

                default:
                    $link = 'index.php?Itemid=' . $item->id;
                    break;
            }
        } else {
            $link = 'index.php';
        }

        $app = Factory::getApplication();
        $secure = ($app->get('force_ssl', 0) === 2) ? 1 : -1;

        if ($item) {
            $secure = $item->getParams()->get('secure', $secure) === 1 ? 1 : 2;
        }

        return Route::_($link, true, $secure);
    }
}
