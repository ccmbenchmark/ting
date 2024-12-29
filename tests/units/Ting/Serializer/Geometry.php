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

namespace tests\units\CCMBenchmark\Ting\Serializer;

use atoum;
use Brick\Geo\IO\WKBReader;
use CCMBenchmark\Ting\Serializer\RuntimeException;

class Geometry extends atoum
{
    public function testUnserializeShouldReturnGeometryObject()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->object(
                $geometrySerializer->unserialize(
                    hex2bin("00000000010100000000000000000024400000000000003440")
                )
            )
                ->isInstanceOf(\Brick\Geo\Geometry::class);
    }

    public function testUnserializeWithNullValueShouldReturnNull()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->variable($geometrySerializer->unserialize(null))
                ->isNull();
    }

    public function testSerializeShouldReturnStringValue()
    {
        $this->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->string($geometrySerializer->serialize(
                (new WKBReader())->read(hex2bin('010100000000000000000024400000000000003440'))
            ))
                ->isIdenticalTo(hex2bin("00000000010100000000000000000024400000000000003440"));
    }

    public function testSerializeWithNullValueShouldReturnNull()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->variable($geometrySerializer->serialize(null))
                ->isNull();
    }

    public function testRuntimeExceptionWhenPackageNotPresent()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->and($this->function->class_exists = false)
            ->exception(function () use ($geometrySerializer): void {
                $geometrySerializer->unserialize(
                    hex2bin("00000000010100000000000000000024400000000000003440")
                );
            })
                ->isInstanceOf(RuntimeException::class)
            ->exception(function () use ($geometrySerializer): void {
                $geometrySerializer->serialize(
                    (new WKBReader())->read(hex2bin('010100000000000000000024400000000000003440'))
                );
            })
                ->isInstanceOf(RuntimeException::class);
    }

    public function testUnserializeThrowExceptionOnIncorrectData()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->exception(function () use ($geometrySerializer): void {
                $geometrySerializer->unserialize("Incorrect data");
            })
                ->isInstanceOf(\UnexpectedValueException::class)
        ;
    }

    public function testSerializeThrowExceptionOnIncorrectData()
    {
        $this
            ->if($geometrySerializer = new \CCMBenchmark\Ting\Serializer\Geometry())
            ->exception(function () use ($geometrySerializer): void {
                $geometrySerializer->serialize((new \StdClass()));
            })
                ->isInstanceOf(\UnexpectedValueException::class)
        ;
    }
}
