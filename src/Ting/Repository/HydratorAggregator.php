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

use CCMBenchmark\Ting\Serializer\RuntimeException;
use SplDoublyLinkedList;

class HydratorAggregator extends Hydrator
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
     * @var SplDoublyLinkedList
     */
    protected $config;

    public function __construct()
    {
        $this->config = new SplDoublyLinkedList();
    }

    /**
     * @param callable $callableForId
     * @return $this
     */
    public function callableIdIs(callable $callableForId)
    {
        $this->callableForId = $callableForId;
        return $this;
    }

    /**
     * @param callable $callableForData
     * @return $this
     */
    public function callableDataIs(callable $callableForData)
    {
        $this->callableForData = $callableForData;
        return $this;
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

    public function aggregate($source, $sourceIdentifier, $target, $targetIdentifier, $targetSetter)
    {
        $this->config->push([
            'source' => $source,
            'sourceIdentifier' => $sourceIdentifier,
            'target' => $target,
            'targetIdentifier' => $targetIdentifier,
            'targetSetter' => $targetSetter
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

        $aggregate = [];
        $result = null;
        $references = [];
        $results = [];

        foreach ($this->result as $key => $columns) {
            $result = $this->hydrateColumns(
                $this->result->getConnectionName(),
                $this->result->getDatabase(),
                $columns
            );

            $founds = [];

            foreach ($this->config as $config) {
                if (isset($result[$config['source']]) === true) {
                    $references[$config['source'] . '-' . $result[$config['source']]->{$config['sourceIdentifier']}()] = $result[$config['source']];
                    $founds[] = $config['source'];
                    $key = '#ting#'
                        . $config['source']
                        . '-to-' . $config['target']
                        . '-' . $result[$config['target']]->{$config['targetIdentifier']}();
                    $sourceIdentifier = $result[$config['source']]->{$config['sourceIdentifier']}();
                    $targetIdentifier = $result[$config['target']]->{$config['targetIdentifier']}();

                    $aggregate[$key]['source'] = $config['source'];
                    $aggregate[$key]['target'] = $config['target'];
                    $aggregate[$key]['targetIdentifier'] = $targetIdentifier;
                    $aggregate[$key][$sourceIdentifier] = $result[$config['source']];
                }

                if (isset($result[$config['target']]) === true) {
                    $references[$config['target'] . '-' . $result[$config['target']]->{$config['targetIdentifier']}()] = $result[$config['target']];
                }
            }

            foreach ($founds as $found) {
                unset($result[$found]);
            }

            $results[$key] = $result;
        }

        $aggregateSpl = new SplDoublyLinkedList();
        foreach ($aggregate as $ag) {
            $aggregateSpl->push($ag);
        }

        foreach ($aggregateSpl as $index => $ag) {
            $dependencyIndex = array_search($ag['target'], array_column($aggregate, 'source'));

            if ($dependencyIndex !== false && $dependencyIndex < $index) {
                $aggregateSpl->offsetUnset($index);
                $aggregateSpl->add($dependencyIndex, $ag);
            }
        }

        foreach ($aggregateSpl as $key => $ag) {
            $source = $ag['source'];
            $target = $ag['target'];
            $targetReferenceName = $ag['target'] . '-' . $ag['targetIdentifier'];
            $config = null;

            foreach ($this->config as $config) {
                if ($config['target'] === $target && $config['source'] === $source) {
                    break;
                }
            }

            if ($config !== null && $ag !== null) {
                unset($ag['source']);
                unset($ag['target']);
                unset($ag['targetIdentifier']);
                $references[$targetReferenceName]->{$config['targetSetter']}($ag);
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
}
