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

namespace Joomla\Component\Externallogin\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Component\Externallogin\Administrator\Service\HTML\ServersService;
use Joomla\Component\Externallogin\Administrator\Service\HTML\UsersService;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Component class for com_externallogin.
 *
 * This class implements modern Joomla 4.x/5.x component architecture:
 * - Dependency injection via service provider
 * - Custom HTML helpers for view rendering
 * - Router service for SEF URLs
 * - Bootable extension for initialization
 *
 * @since  5.0.0
 */
class ExternalloginComponent extends MVCComponent implements
    BootableExtensionInterface,
    RouterServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;

    /**
     * The component container.
     *
     * @since 5.0.0
     */
    private static ?ContainerInterface $componentContainer = null;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     *
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     *
     * @param ContainerInterface $container The container
     *
     * @since  5.0.0
     */
    public function boot(ContainerInterface $container)
    {
        // Store the component container for later use
        self::$componentContainer = $container;

        // Register custom HTML helpers with modern namespaced classes
        // These are used in views for rendering server and user lists
        $registry = $this->getRegistry();
        $registry->register('servers', ServersService::class);

        // Use 'externallogin_users' to avoid conflict with com_users' 'users' service
        $registry->register('externallogin_users', UsersService::class);

        // Note: Helper methods are available via ExternalloginHelper static class
    }

    /**
     * Get the component container.
     *
     * @throws RuntimeException if the container has not been booted
     *
     * @return ContainerInterface The component container
     *
     * @since 5.0.0
     */
    public static function getComponentContainer(): ContainerInterface
    {
        if (self::$componentContainer === null) {
            throw new RuntimeException('Component has not been booted yet');
        }

        return self::$componentContainer;
    }
}
