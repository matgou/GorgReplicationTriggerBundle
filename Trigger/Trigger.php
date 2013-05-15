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

/**
 * Class to represent a Trigger
 * Author: Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
abstract class Trigger
{
    protected $logger;
    protected $initialEntity;
 
    /**
     * Create a new trigger
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger               = $logger;
    }

    /**
     * This action is lauch when an event is call
     * This method call transform method to adapt object and persit to send the transform object to entity manager
     *
     * @param LoggerInterface $logger
     */
    public function onChange(TriggerEvent $event)
    {
        if($event->getEntity())
        {
            $this->initialEntity = $event->getEntity();
            $entity = $this->transform($event->getEntity(), $event->getAction());
            $this->persist($entity);
        } else {
            $this->logger->info(sprintf('Entity is flase, so ending workflow and not executing event !!!'));
        }
    }

    /**
     * Transform the entity to an acceptable form for destination
     * 
     * @param $entity the entity to transform
     * @param $action the action is string in {"new", "update", "delete"}
     *
     * @return the transformed entity
     */
    abstract protected function transform($entity, $action);

    /**
     * Persist the transformed entity
     * 
     * @param $entity the transformed entity
     */
    abstract protected function persist($entity);
}
