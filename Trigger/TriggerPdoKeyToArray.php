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
class TriggerPdoKeyToArray extends TriggerForwarder
{
    protected $eventDispater;
    protected $pdo;
    protected $config;

    /**
     * Create a new trigger
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, $eventDispatcher, \PDO $pdo, Array $config)
    {
        $this->pdo = $pdo;
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
       $sql = $this->config['fetch'];
       $entityAsArray = $this->executeQuery($sql, $entity);

       return new TriggerEvent($entityAsArray, $action);
    }

    private function cleanParameters($sql, Array $data) {
        foreach ($data as $key => $value) {
            if(!preg_match("/:$key/", $sql)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    protected function executeQuery($sql, Array $data)
    {
        $this->logger->info(sprintf('data : %s', serialize($data)));
        $data = $this->cleanParameters($sql, $data);
        $this->logger->info(sprintf('Execute query : "%s" with data "%s"', $sql, serialize($data)));
        $sth = $this->pdo->prepare($sql);
        $sth->execute($data);
        if($sth->errorCode() === '00000') {
            return $sth->fetch(\PDO::FETCH_ASSOC);
        } else {
            $errorInfo = $sth->errorInfo();
            throw new \Exception(sprintf('Exceptition in pdo see log : %s', $errorInfo[2]));
        }
    }
}
