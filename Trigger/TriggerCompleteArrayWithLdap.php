<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/

namespace Gorg\Bundle\ReplicationTriggerBundle\Trigger;

use Gorg\Bundle\ReplicationTriggerBundle\Event\TriggerEvent;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Gorg\Bundle\LdapOrmBundle\Ldap\LdapEntityManager;

/**
 * Class to represent a Trigger
 * Author: Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
class TriggerCompleteArrayWithLdap extends TriggerForwarder
{
    protected $entityManager;

    /**
     * Create a new trigger
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, $eventDispatcher, LdapEntityManager $entityManager, Array $config)
    {
        $this->entityManager = $entityManager;
        return parent::__construct($logger, $eventDispatcher, $config);
    }

    /**
     * Transform the entity to an acceptable form for destination
     * 
     * @param $entity the entity to transform
     * @param $action the action is string in {"new", "update", "delete"}
     *
     * @return the transformed entity
     */
    protected function transform($entity, $action)
    {
       $this->logger->info(sprintf('complet array : %s', serialize($entity)));
       $repository = $this->entityManager->getRepository($this->config['class']);
       $className = $this->config['class'];
       $keyAttribute = $this->config['key'];
       $ldapEntity = $repository->findOneBy($keyAttribute, $entity[$keyAttribute]);
       if(!$ldapEntity) {
           $this->logger->info(sprintf('Unable to complete array beacause no entity with key %s as value %s', $keyAttribute, $entity[$keyAttribute]));
           return $entity;
       }

       foreach($this->config['mapping'] as $ldapKey => $arrayKey) {
           $getter = 'get' . ucfirst($ldapKey);
           $entity[$arrayKey] = $ldapEntity->$getter();
       }

       return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function persist($entity)
    {
        $event = new TriggerEvent($entity, 'new');
        foreach($this->config['target'] as $forwardEventName) {
            $this->eventDispatcher->dispatch($forwardEventName, $event);
        }
    }
}
