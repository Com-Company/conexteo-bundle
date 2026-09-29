<?php

namespace Connected\Conexteo\DependencyInjection;

use Connected\Conexteo\Transport\ConexteoTransport;
use Connected\Conexteo\Transport\ConexteoTransportFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Notifier\Transport\Dsn;

class ConexteoExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        if (null === $config['dsn']) {
            return;
        }

        // Le composant Notifier n'expose pas ses transports individuellement : on
        // enregistre celui-ci en service à part pour rendre la lecture des réponses
        // accessible par autowiring.
        $container->register(ConexteoTransport::class, ConexteoTransport::class)
            ->setFactory([new Reference(ConexteoTransportFactory::class), 'create'])
            ->setArguments([(new Definition(Dsn::class))->setArguments([$config['dsn']])])
        ;
    }
}
