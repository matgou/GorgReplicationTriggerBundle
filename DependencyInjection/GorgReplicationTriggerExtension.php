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
        $master = $config['pdo_master'];
        /* Query the database to obtain trigger list */
        $masterDB = new \PDO($master['dsn'], $master['user'], $master['password']);
        $stmt = $masterDB->prepare("select name, type, config, event, completer, entityManager,	config FROM trigger_place");
        $stmt->execute();

        while($triggerConfig = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $triggerName   = $triggerConfig['name'];
            $type          = $triggerConfig['type'];
            $config        = json_decode($triggerConfig['config'], true);
            $onChange      = json_decode($triggerConfig['event'], true);
            if(isset($triggerConfig['completer'])) {
                $completer = $triggerConfig['completer'];
            }
            if(strcmp($type,"pdoSingleRaw")==0) {
                $entityManager = $triggerConfig['entityManager'];
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerToPdoSingleRaw',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_replication_trigger_pdo_' . $entityManager),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            }elseif(strcmp($type,"pdoMultiRaw")==0) {
                $entityManager = $triggerConfig['entityManager'];
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerToPdoMultiRaw',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_replication_trigger_pdo_' . $entityManager),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            } elseif(strcmp($type, "emailActivator") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\GramApiServerBundle\Trigger\TriggerActiveEmail',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_ldap_orm.entity_manager'),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            } elseif(strcmp($type, "ldapKeyToArray") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerLdapKeyToArray',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_ldap_orm.entity_manager'),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $definitionInContainer = $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);

            } elseif(strcmp($type, "arrayToLdapDiff") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerArrayToLdapDiff',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_ldap_orm.entity_manager'),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $definitionInContainer = $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
                if(isset($completer)) {
                    $definitionInContainer->addMethodCall('setCompleter', array(
                              new Reference($completer)
                          ));
                }

            } elseif(strcmp($type, "completeArrayWithLdap") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerCompleteArrayWithLdap',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_ldap_orm.entity_manager'),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            } elseif(strcmp($type, "arrayToProfileLdapDiff") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\GramApiServerBundle\Trigger\TriggerArrayToProfileLdapDiff',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_ldap_orm.entity_manager'),
                        $config,
                    )
                );


                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $definitionInContainer = $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
                if(isset($completer)) {
                    $definitionInContainer->addMethodCall('setCompleter', array(
                              new Reference($completer)
                          ));
                }
            } elseif(strcmp($type, "pdoKeyToArray") == 0) {
                $entityManager = $triggerConfig['entityManager'];
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerPdoKeyToArray',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
                        new Reference('gorg_replication_trigger_pdo_' . $entityManager),
                        $config,
                    )
                );

                $def->addTag('kernel.event_listener', array('event' => $onChange, 'method' => "onChange"));
                $container->setDefinition('gorg_replication_trigger_' . $triggerName, $def);
            } elseif(strcmp($type, "forwarder") == 0) {
                $def = new Definition(
                    'Gorg\Bundle\ReplicationTriggerBundle\Trigger\TriggerForwarder',
                    array(
                        new Reference('logger'),
                        new Reference('event_dispatcher'),
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
