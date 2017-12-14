<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Repository\Hydrator\Relation;
use CCMBenchmark\Ting\Repository\Hydrator\RelationMany;

class HydratorRelational extends Hydrator
{
    /**
     * @var callable
     */
    protected $callableForId;

    /**
     * @var callable
     */
    protected $callableForData;

    /**
     * @var callable
     */
    protected $callableFinalizeAggregate;

    /**
     * @var \SplDoublyLinkedList
     */
    protected $config;

    /**
     * @var bool
     */
    protected $identityMap = true;

    public function __construct()
    {
        $this->config = new \SplDoublyLinkedList();
    }

    /**
     * @param bool $enable
     * @throws Exception
     * @return void
     */
    public function identityMap($enable)
    {
        if ((bool) $enable === false) {
            throw new Exception('identityMap can\'t be disabled for this Hydrator');
        }
    }

    /**
     * @param callable $callableFinalizeAggregate
     * @return $this
     */
    public function callableFinalizeAggregate(callable $callableFinalizeAggregate)
    {
        $this->callableFinalizeAggregate = $callableFinalizeAggregate;
        return $this;
    }

    public function addRelation(Relation $relation)
    {
        $this->config->push([
            'source' => $relation->getSource(),
            'sourceIdentifier' => $relation->getSourceIdentifier(),
            'target' => $relation->getTarget(),
            'targetIdentifier' => $relation->getTargetIdentifier(),
            'targetSetter' => $relation->getSetter(),
            'many' => $relation instanceof RelationMany
        ]);
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        $configAsArray = iterator_to_array($this->config);

        foreach ($this->config as $index => $config) {
            $dependencyIndex = array_search($config['target'], array_column($configAsArray, 'source'));

            if ($dependencyIndex !== false && $dependencyIndex < $index) {
                $this->config->offsetUnset($index);
                $this->config->add($dependencyIndex, $config);
            }
        }

        $references = [];
        $results = [];
        $ressources = [];

        foreach ($this->result as $columns) {
            $result = $this->hydrateColumns(
                $this->result->getConnectionName(),
                $this->result->getDatabase(),
                $columns
            );

            foreach ($this->config as $config) {
                if (isset($result[$config['target']]) === false) {
                    continue;
                }

                $keyTarget = $config['target'] . '-' . $this->getIdentifiers($config['target'], $result[$config['target']], $config['targetIdentifier']);

                if (isset($references[$keyTarget]) === false) {
                    $references[$keyTarget] = $result[$config['target']];
                }

                if (isset($result[$config['source']]) === true) {
                    $keySource = $config['source'] . '-' . $this->getIdentifiers($config['source'], $result[$config['source']], $config['sourceIdentifier']);

                    if (isset($references[$keySource]) === false) {
                        $references[$keySource] = $result[$config['source']];
                    }

                    if (isset($ressources[$keyTarget][$config['targetSetter']]) === false) {
                        $ressources[$keyTarget][$config['targetSetter']] = [];
                    }

                    if ($config['many'] === true) {
                        if (isset($ressources[$keyTarget][$config['targetSetter']][$keySource]) === false) {
                            $ressources[$keyTarget][$config['targetSetter']][$keySource] = $references[$keySource];
                        }
                    } else {
                        $ressources[$keyTarget][$config['targetSetter']] = $references[$keySource];
                    }

                    unset($result[$config['source']]);
                }
            }

            if (isset($results[$keyTarget]) === false) {
                $results[$keyTarget] = $result;
            }
        }

        foreach ($references as $referenceKey => $reference) {
            if (isset($ressources[$referenceKey]) === false) {
                continue;
            }

            foreach ($ressources[$referenceKey] as $setter => $valuesToSet) {
                $reference->$setter($valuesToSet);
            }
        }

        foreach ($results as $result) {
            yield $this->finalizeAggregate($result);
        }
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     */
    private function finalizeAggregate($result)
    {
        if ($this->callableFinalizeAggregate === null) {
            return $result;
        }

        $callableFinalizeAggregate = $this->callableFinalizeAggregate;
        return $callableFinalizeAggregate($result);
    }

    private function getIdentifiers($table, $entity, $identifier)
    {
        if ($identifier !== null) {
            return $entity->{$identifier}();
        }

        $id = '';
        foreach ($this->metadataList[$table]->getPrimaries() as $columnName => $primary) {
            $id .= $entity->{$this->metadataList[$table]->getGetter($primary['fieldName'])}() . '-';
        }

        return $id;
    }
}
