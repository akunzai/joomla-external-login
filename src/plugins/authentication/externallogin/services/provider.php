<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Authentication\Externallogin\Extension\Externallogin;

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $config = (array) PluginHelper::getPlugin('authentication', 'externallogin');
                $plugin = new Externallogin($dispatcher, $config);
                $plugin->setApplication(Factory::getApplication());
                return $plugin;
            }
        );
    }
};
