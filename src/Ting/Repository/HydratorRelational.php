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
    protected $referencesRelation = [];

    /**
     * @var array
     */
    private $resources = [];

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
            'target' => $relation->getTarget(),
            'targetSetter' => $relation->getSetter(),
            'many' => $relation instanceof RelationMany
        ]);
    }

    /**
     * @throws Exception
     *
     * @return \Generator
     */
    public function getIterator()
    {
        $this->resolveDependencies();

        $this->referencesRelation = [];
        $this->resources          = [];
        $results                  = [];

        foreach ($this->result as $columns) {
            $result = $this->hydrateColumns($this->result->getConnectionName(), $this->result->getDatabase(), $columns);

            foreach ($this->config as $config) {
                if (isset($result[$config['target']]) === false) {
                    continue;
                }

                $keyTarget = $this->saveTargetReference($config, $result);
                if (isset($result[$config['source']]) === true) {
                    $keySource = $this->saveSourceReference($config, $result);
                    $this->saveResourceFor($config, $keyTarget, $keySource);
                    unset($result[$config['source']]);
                }
            }

            if (isset($results[$keyTarget]) === false) {
                $results[$keyTarget] = $result;
            }
        }

        $this->assignResourcesToReferences();

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
     * @throws Exception
     *
     * @return string
     */
    private function saveTargetReference($config, $result)
    {
        $keyTarget = $config['target'] . '-' . $this->getIdentifiers($config['target'], $result[$config['target']]);

        if (isset($this->referencesRelation[$keyTarget]) === false) {
            $this->referencesRelation[$keyTarget] = $result[$config['target']];
        }

        return $keyTarget;
    }

    /**
     * @param array $config
     * @param array $result
     *
     * @throws Exception
     *
     * @return string
     */
    private function saveSourceReference($config, $result)
    {
        $keySource = $config['source'] . '-' . $this->getIdentifiers($config['source'], $result[$config['source']]);

        if (isset($this->referencesRelation[$keySource]) === false) {
            $this->referencesRelation[$keySource] = $result[$config['source']];
        }

        return $keySource;
    }

    /**
     * @param array  $config
     * @param string $keyTarget
     * @param string $keySource
     */
    private function saveResourceFor($config, $keyTarget, $keySource)
    {
        if (isset($this->resources[$keyTarget][$config['targetSetter']]) === false) {
            $this->resources[$keyTarget][$config['targetSetter']] = [];
        }

        if ($config['many'] === true) {
            if (isset($this->resources[$keyTarget][$config['targetSetter']][$keySource]) === false) {
                $this->resources[$keyTarget][$config['targetSetter']][$keySource] = $this->referencesRelation[$keySource];
            }
        } else {
            $this->resources[$keyTarget][$config['targetSetter']] = $this->referencesRelation[$keySource];
        }
    }

    private function assignResourcesToReferences()
    {
        foreach ($this->referencesRelation as $referenceKey => $reference) {
            if (isset($this->resources[$referenceKey]) === false) {
                continue;
            }

            foreach ($this->resources[$referenceKey] as $setter => $valuesToSet) {
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

    /**
     * @param string $table
     * @param object $entity
     *
     * @throws Exception
     *
     * @return string
     */
    private function getIdentifiers($table, $entity)
    {
        $id = '';
        foreach ($this->metadataList[$table]->getPrimaries() as $columnName => $primary) {
            $id .= $entity->{$this->metadataList[$table]->getGetter($primary['fieldName'])}() . '-';
        }

        if ($id === '') {
            throw new Exception(sprintf('No primary found for "%s"', $this->metadataList[$table]->getEntity()));
        }

        return $id;
    }
}
