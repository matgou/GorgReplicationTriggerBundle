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
abstract class TriggerToPdo extends TriggerForwarder
{
    protected $pdo;
    protected $config;

    public function __construct(LoggerInterface $logger, $eventDispatcher, \PDO $pdo, Array $config)
    {
        $this->pdo     = $pdo;
        $this->config  = $config;

        parent::__construct($logger, $eventDispatcher, $config);
    }

    protected function cleanParameters($sql, Array $data) {
        foreach ($data as $key => $value) {
            if(!preg_match("/:$key/", $sql)) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function persist($entity) 
    {
        foreach($entity as $query) {
            $this->executeQuery($query['sql'], $query['data']);
        }

        $trigger = new TriggerEvent($this->initialEntity, 'new');
        foreach($this->config['target'] as $forwardEventName) {
            $this->eventDispatcher->dispatch($forwardEventName, $trigger);
        }
    }
    
    protected function executeQuery($sql, Array $data)
    {
        $data = $this->cleanParameters($sql, $data);
        $this->logger->info(sprintf('Execute query : "%s" with data "%s"', $sql, serialize($data)));
        $sth = $this->pdo->prepare($sql);
        $sth->execute($data);
        if($sth->errorCode() === '00000') {
            return $sth->fetch();
        } else {
            $errorInfo = $sth->errorInfo();
            throw new \Exception(sprintf('Exceptition in pdo see log : %s', $errorInfo[2]));
        }
    }
}
