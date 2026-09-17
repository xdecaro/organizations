<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('xdecaro\\Component\\Organizations'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('xdecaro\\Component\\Organizations'));

        $container->share(
            CoreIntegrationService::class,
            static fn(): CoreIntegrationService => new CoreIntegrationService()
        );
        $container->share(
            OrganizationProviderService::class,
            static fn(Container $container): OrganizationProviderService => new OrganizationProviderService(
                $container->get(DatabaseInterface::class),
                $container->get(CoreIntegrationService::class)
            )
        );
        $container->share(
            DuplicateService::class,
            static fn(Container $container): DuplicateService => new DuplicateService(
                $container->get(DatabaseInterface::class)
            )
        );
        $container->share(
            PeopleIntegrationService::class,
            static fn(): PeopleIntegrationService => new PeopleIntegrationService()
        );
        $container->share(
            PersonAppointmentsService::class,
            static fn(Container $container): PersonAppointmentsService => new PersonAppointmentsService(
                $container->get(DatabaseInterface::class)
            )
        );
        $container->share(
            OrganizationBodiesService::class,
            static fn(Container $container): OrganizationBodiesService => new OrganizationBodiesService(
                $container->get(DatabaseInterface::class)
            )
        );

        $container->set(
            ComponentInterface::class,
            static function (Container $container): ComponentInterface {
                $component = new OrganizationsComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setCoreIntegrationService($container->get(CoreIntegrationService::class));
                $component->setOrganizationProviderService($container->get(OrganizationProviderService::class));
                $component->setDuplicateService($container->get(DuplicateService::class));
                $component->setPeopleIntegrationService($container->get(PeopleIntegrationService::class));
                $component->setPersonAppointmentsService($container->get(PersonAppointmentsService::class));
                $component->setOrganizationBodiesService($container->get(OrganizationBodiesService::class));

                return $component;
            }
        );
    }
};
