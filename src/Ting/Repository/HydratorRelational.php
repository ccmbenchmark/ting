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

final class HydratorRelational extends Hydrator
{
    /**
     * @var callable
     */
    private $callableFinalizeAggregate;

    /**
     * @var \SplDoublyLinkedList
     */
    private $config;

    /**
     * @var array
     */
    protected $myreferences = [];

    /**
     * @var array
     */
    private $ressources = [];

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
        $this->resolveDependencies();

        $this->myreferences = [];
        $this->ressources   = [];
        $results            = [];

        foreach ($this->result as $columns) {
            $result = $this->hydrateColumns($this->result->getConnectionName(), $this->result->getDatabase(), $columns);

            foreach ($this->config as $config) {
                if (isset($result[$config['target']]) === false) {
                    continue;
                }

                $keyTarget = $this->saveTargetReference($config, $result);

                if (isset($result[$config['source']]) === true) {
                    $keySource = $this->saveSourceReference($config, $result);
                    $this->saveRessourceFor($config, $keyTarget, $keySource);
                    unset($result[$config['source']]);
                }
            }

            if (isset($results[$keyTarget]) === false) {
                $results[$keyTarget] = $result;
            }
        }

        $this->assignRessourcesToReferences();

        foreach ($results as $result) {
            yield $this->finalizeAggregate($result);
        }
    }

    private function resolveDependencies()
    {
        $configAsArray = iterator_to_array($this->config);

        foreach ($this->config as $index => $config) {
            $dependencyIndex = array_search($config['target'], array_column($configAsArray, 'source'));

            if ($dependencyIndex !== false && $dependencyIndex < $index) {
                $this->config->offsetUnset($index);
                $this->config->add($dependencyIndex, $config);
            }
        }
    }

    /**
     * @param array $config
     * @param array $result
     *
     * @return string
     */
    private function saveTargetReference($config, $result)
    {
        $keyTarget = $config['target'] . '-'
            . $this->getIdentifiers($config['target'], $result[$config['target']], $config['targetIdentifier']);

        if (isset($this->myreferences[$keyTarget]) === false) {
            $this->myreferences[$keyTarget] = $result[$config['target']];
        }

        return $keyTarget;
    }

    /**
     * @param array $config
     * @param array $result
     *
     * @return string
     */
    private function saveSourceReference($config, $result)
    {
        $keySource = $config['source'] . '-'
            . $this->getIdentifiers($config['source'], $result[$config['source']], $config['sourceIdentifier']);

        if (isset($this->myreferences[$keySource]) === false) {
            $this->myreferences[$keySource] = $result[$config['source']];
        }

        return $keySource;
    }

    /**
     * @param array  $config
     * @param string $keyTarget
     * @param string $keySource
     */
    private function saveRessourceFor($config, $keyTarget, $keySource)
    {
        if (isset($this->ressources[$keyTarget][$config['targetSetter']]) === false) {
            $this->ressources[$keyTarget][$config['targetSetter']] = [];
        }

        if ($config['many'] === true) {
            if (isset($this->ressources[$keyTarget][$config['targetSetter']][$keySource]) === false) {
                $this->ressources[$keyTarget][$config['targetSetter']][$keySource] = $this->myreferences[$keySource];
            }
        } else {
            $this->ressources[$keyTarget][$config['targetSetter']] = $this->myreferences[$keySource];
        }
    }

    private function assignRessourcesToReferences()
    {
        foreach ($this->myreferences as $referenceKey => $reference) {
            if (isset($ressources[$referenceKey]) === false) {
                continue;
            }

            foreach ($this->ressources[$referenceKey] as $setter => $valuesToSet) {
                $reference->$setter($valuesToSet);
            }
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
