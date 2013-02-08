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

namespace Gorg\Bundle\ReplicationTriggerBundle\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class for represent AccountEvent 
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @category API
 * @package  GramApiServerBundle
 * @author   Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @license  GNU General Public License
 */
class TriggerEvent extends GenericEvent
{
    protected $entity;
    private $action;

    public function __construct($entity = null, $action = 'new')
    {
        $this->entity = $entity;
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
