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
class TriggerToPdoMultiRaw extends TriggerToPdo
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

    private function getDBColumn($sql, $data, $columnName)
    {
        $columnValues = array();
        $data = $this->cleanParameters($sql, $data);
        $this->logger->info(sprintf('Execute query : "%s" with data "%s"', $sql, serialize($data)));
        $sth = $this->pdo->prepare($sql);
        $sth->execute($data);
        if($sth->errorCode() === '00000') {
            $rawInDBData = $sth->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $errorInfo = $sth->errorInfo();
            throw new \Exception(sprintf('Exceptition in pdo see log : %s', $errorInfo[2]));
        }

        foreach($rawInDBData as $dbData) {
            $columnValues[] = $dbData[$columnName];
        }

        return $columnValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function transform($entity, $action)
    {
        $data = $this->objectToArray($entity);
        // Test if entity exist
        $arrayName = $this->config['arrayName'];
        $sql = $this->config['fetch'];
        $return = array();
        $sqlFetchAll = preg_replace('/:' . $arrayName . '/', '"%"', $sql);

        /* get data in db and delete if obsolete */
        $dataInDB = $this->getDBColumn($sqlFetchAll, $data, $arrayName);
        $dataToDelete = array_diff($dataInDB, is_null($data[$arrayName]) ? array() : $data[$arrayName]);
        $dataTemp = $data;
        foreach($dataToDelete as $valueToDelete) {
            $dataTemp[$arrayName] = $valueToDelete;
            $this->executeQuery($this->config['remove'], $dataTemp);
        }

        /* Insert or update new data in db */
        if(!empty($data[$arrayName])) foreach($data[$arrayName] as $value) {
            $value = "'" . $value . "'";
            $singleRawSql = preg_replace('/:' . $arrayName . '/', $value, $sql);
            $dbData = $this->executeQuery($singleRawSql, $data);
            if(!empty($dbData)) {
                $outSql = preg_replace('/:' . $arrayName . '/', $value, $this->config['update']);
            } else {
                $outSql = preg_replace('/:' . $arrayName . '/', $value, $this->config['new']);
            }

            $return[] = array(
                'sql' => $outSql,
                'data' => $data,
            );
        }

        return $return;
    }
}
