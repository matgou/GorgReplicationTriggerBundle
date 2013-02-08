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
 *
 * config format :
 *   triggerName:
 *       entityManager: pdo_session1
 *       new:    "INSERT INTO ... VALUES (:value1, :value2)
 *       remove: "DELETE FROM ... WHERE key = :value1"
 *       update: "UPDATE ... SET field2 = :value2 WHERE  key =  :value1"
 *       mapping:
 *           value1:    objectField1
 *           value2:    objectField2
 *
 * Author: Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
class TriggerToPdoSingleRaw extends TriggerToPdo
{
    /**
     * Convert object into array using mapping in configuration
     */
    private function objectToArray($entity)
    {
        $mapping = $this->config['mapping'];
        $returnArray = array();
        foreach($mapping as $key => $column) {
            $getter = 'get' . ucfirst($column);
            $returnArray[$key] = $entity->$getter();
        }

        return $returnArray;
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($entity, $action)
    {
        $data = $this->objectToArray($entity);
        // Test if entity exist
        if(strcmp($action, 'new') == 0) {
            $sql = $this->config['fetch'];
            $dbData = $this->executeQuery($sql, $data);
            if(!empty($dbData)) {
                $action='update';
            }
        }

        $query = array();
        $query['sql'] = $this->config[$action];
        $query['data'] = $data;
        return array($query);
    }
}
