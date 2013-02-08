<?php

namespace Gorg\Bundle\ReplicationTriggerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GorgReplicationTriggerExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        /* Load all pdo connections */
        foreach($config['pdo_connections'] as $pdoSessionName => $pdoConfig) {
            $container->setDefinition('gorg_replication_trigger_pdo_' . $pdoSessionName, new Definition(
                'PDO',
                array($pdoConfig['dsn'], $pdoConfig['user'], $pdoConfig['password'])
            ));
        }

        foreach($config['trigger'] as $triggerName => $triggerConfig) {
            $type          = $triggerConfig['type'];
            $entityManager = $triggerConfig['entityManager'];
            $config        = $triggerConfig['config'];
            $onChange      = $triggerConfig['event'];

            if(strcmp($type,"pdoSingleRaw")==0) {
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerToPdoSingleRaw',
                    array(
                        new Reference('logger'),
                        new Reference('gorg_replication_trigger_pdo_' . $entityManager),
                        $config,
                    )
                );
                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));


                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            } else {
                throw new \Exception(sprintf('The type %s does not exist in trigger builder', $type));
            }
        }
    }
}
