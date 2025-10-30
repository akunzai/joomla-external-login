<?php

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    /**
     * @since 5.0.0
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\ExternalloginAdmin'));
        $container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\ExternalloginAdmin\\Administrator\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
