<?php
namespace xdecaro\Component\Organizations\Administrator\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('xdecaro\\Component\\Organizations'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('xdecaro\\Component\\Organizations'));
        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->set(ComponentInterface::class, static fn (Container $container): ComponentInterface => new OrganizationsComponent($container->get(ComponentDispatcherFactoryInterface::class), $container->get(MVCFactoryInterface::class)));
    }
};
