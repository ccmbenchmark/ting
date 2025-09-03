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

namespace CCMBenchmark\Ting\Serializer;

use Brick\Geo\Geometry as BrickGeometry;
use Brick\Geo\IO\WKBWriter;
use Brick\Geo\IO\WKBReader;
use Exception;
use UnexpectedValueException;

use function sprintf;
use function strlen;
use function hex2bin;
use function substr;

// Geometry format in MySQL consist in 4 bytes to indicate the SRID then the WKB representation
// CF https://dev.mysql.com/doc/refman/8.0/en/gis-data-formats.html#gis-internal-format
// Brick/Geo only handle WKB, so we have to add/remove the SRID part

final class Geometry implements SerializerInterface
{
    private const SRID = '00000000';

    public function __construct()
    {
    }

    /**
     * @inheritdoc
     *
     * @param mixed $toSerialize
     * @param array{} $options
     */
    public function serialize($toSerialize, array $options = []): ?string
    {
        if (!class_exists(WKBWriter::class) || !class_exists(BrickGeometry::class)) {
            throw new RuntimeException("Package brick/geo is required to handle Geometry. Please run `composer require brick/geo`");
        }

        if ($toSerialize === null) {
            return null;
        }

        if (!$toSerialize instanceof BrickGeometry) {
            throw new UnexpectedValueException(
                sprintf(
                    'Expected an instance of "%s".',
                    BrickGeometry::class
                )
            );
        }
        return hex2bin(self::SRID) . (new WKBWriter())->write($toSerialize);
    }

    /**
     * @inheritdoc
     *
     * @param null|string $serialized
     * @param array{} $options
     */
    public function unserialize($serialized, array $options = []): ?BrickGeometry
    {
        if (!class_exists(WKBReader::class)) {
            throw new RuntimeException("Package brick/geo is required to handle Geometry. Please run `composer require brick/geo`");
        }

        if ($serialized === null) {
            return null;
        }

        try {
            return (new WKBReader())->read(
                substr(
                    $serialized,
                    strlen(
                        hex2bin(self::SRID)
                    )
                )
            );
        } catch (Exception $e) {
            throw new UnexpectedValueException(sprintf(
                'Error during Geometry conversion "%s".',
                $e->getMessage()
            ), $e->getCode(), $e);
        }

    }
}
