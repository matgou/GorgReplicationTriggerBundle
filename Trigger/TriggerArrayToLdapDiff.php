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
class TriggerArrayToLdapDiff extends TriggerForwarder
{
    protected $entityManager;
    protected $completer = false;

    public function setCompleter($completer)
    {
        $this->completer = $completer;
    }

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
       $repository = $this->entityManager->getRepository($this->config['class']);
       $new = false;
       $className = $this->config['class'];
       $keyAttribute = $this->config['key'];
       if(isset($entity[$keyAttribute])) {
           $ldapEntity = $repository->findOneBy($keyAttribute, $entity[$keyAttribute]);
       } else {
           $ldapEntity = null;
       }
       if(!$ldapEntity) {
           $ldapEntity = new $className();
           $new = true;
       }

       foreach($this->config['mapping'] as $outKey => $inKey) {
           $setter = 'set' . ucfirst($outKey);
           $this->logger->info('set : ' . $setter);
           if(isset($entity[$inKey])) {
               $this->logger->info('value : ' . $entity[$inKey]);
               $value = $entity[$inKey];
               if(!is_array($value) && preg_match("@^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])@", $value)) {
                   $value = new \DateTime($value);
               }
                
               $ldapEntity->$setter($value);
           }
       }

       if($new && $this->completer) {
           $this->completer->complete($ldapEntity);
       }

       return $ldapEntity;
    }

    /**
     * {@inheritdoc}
     */
    protected function persist($entity)
    {
        $this->entityManager->persist($entity);
        $event = new TriggerEvent($entity, 'new');
        foreach($this->config['target'] as $forwardEventName) {
            $this->logger->info("forward 'TriggerEvent' to " . $forwardEventName);
            $this->eventDispatcher->dispatch($forwardEventName, $event);
        }

        return $event;
    }
}
