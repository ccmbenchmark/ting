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

use stdClass;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Exceptions\HydratorException;
use CCMBenchmark\Ting\Repository\Hydrator\Relation;
use CCMBenchmark\Ting\Repository\Hydrator\RelationMany;
use Generator;
use SplDoublyLinkedList;

use function array_reverse;
use function array_search;
use function array_splice;
use function array_unshift;
use function array_values;
use function in_array;
use function ksort;

/**
 * @template T
 *
 * @template-extends Hydrator<T>
 */
final class HydratorRelational extends Hydrator
{
    /**
     * @var callable|null
     */
    private $callableFinalizeAggregate;

    private SplDoublyLinkedList $config;

    /**
     * @var array
     */
    protected $referencesRelation = [];

    private array $resources = [];

    /**
     * @var bool
     */
    protected $identityMap = true;

    public function __construct()
    {
        parent::__construct();
        $this->config = new SplDoublyLinkedList();
    }

    /**
     * @param bool $enable
     * @throws HydratorException
     * @return void
     */
    public function identityMap($enable): void
    {
        if ((bool) $enable === false) {
            throw new HydratorException('identityMap can\'t be disabled for this Hydrator');
        }
    }

    /**
     * @param callable $callableFinalizeAggregate
     * @return $this
     */
    public function callableFinalizeAggregate(callable $callableFinalizeAggregate): self
    {
        $this->callableFinalizeAggregate = $callableFinalizeAggregate;
        return $this;
    }

    public function addRelation(Relation $relation): void
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
     * @return Generator<int, T|stdClass>
     */
    public function getIterator(): Generator
    {
        if ($this->config->isEmpty()) {
            return $this->hydrateNoAssociation();
        }

        return $this->hydrate();
    }

    private function resolveDependencies(): void
    {
        $order = [];
        foreach ($this->config as $item) {
            if (!in_array($item['target'], $order, true)) {
                array_unshift($order, $item['target']);
            }
            if (!in_array($item['source'], $order, true)) {
                /** @var int $pos */
                $pos = array_search($item['target'], $order, true);
                array_splice($order, $pos + 1, 0, $item['source']);
            }
        }
        $order = array_reverse($order);

        $output = [];
        foreach ($this->config as $config) {
            $index = array_search($config['source'], $order);
            $output[$index] = $config;
        }
        ksort($output);

        $this->config = new SplDoublyLinkedList();
        foreach (array_values($output) as $index => $config) {
            $this->config->add($index, $config);
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
    private function saveTargetReference(array $config, array $result): string
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
    private function saveSourceReference(array $config, array $result): string
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
    private function saveResourceFor(array $config, string $keyTarget, string $keySource): void
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

    private function assignResourcesToReferences(): void
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

    private function hydrate(): Generator
    {
        $this->resolveDependencies();

        $this->referencesRelation = [];
        $this->resources          = [];
        $results                  = [];

        foreach ($this->result as $columns) {
            $result = $this->hydrateColumns($this->result->getConnectionName(), $this->result->getDatabase(), $columns);

            $keyTarget = null;
            foreach ($this->config as $config) {
                if (isset($result[$config['target']]) === false) {
                    continue;
                }

                $keyTarget = $this->saveTargetReference($config, $result);
                if (isset($result[$config['source']])) {
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

    private function hydrateNoAssociation(): Generator
    {
        foreach ($this->result as $columns) {
            yield $this->finalizeAggregate(
                $this->hydrateColumns($this->result->getConnectionName(), $this->result->getDatabase(), $columns)
            );
        }
    }

    /**
     *
     * @return mixed
     */
    private function finalizeAggregate(array $result): mixed
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
     * @throws HydratorException
     *
     * @return string
     */
    private function getIdentifiers($table, $entity): string
    {
        $id = '';
        foreach ($this->metadataList[$table]->getPrimaries() as $primary) {
            $id .= $this->metadataList[$table]->getEntityPropertyByFieldName($entity, $primary['fieldName']) . '-';
        }

        if ($id === '') {
            throw new HydratorException(sprintf('No primary found for "%s"', $this->metadataList[$table]->getEntity()));
        }

        return $id;
    }
}
