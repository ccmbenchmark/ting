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

namespace tests\units\CCMBenchmark\Ting\Entity;

use \mageekguy\atoum;

class Hydrator extends atoum
{
    public function testHydrate()
    {
        $data = array(
            array(
                'name'     => 'fname',
                'orgName'  => 'boo_firstname',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            ),
            array(
                'name'     => 'name',
                'orgName'  => 'boo_name',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Robez-Masson'
            )
        );

        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ));

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Entity\Hydrator(
                $services->get('MetadataRepository'),
                $services->get('UnitOfWork')
            ))
            ->then($data = $hydrator->hydrate($data))
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateShouldHydrateUnknownColumnIntoDbTable()
    {
        $data = array(
            array(
                'name'     => 'fname',
                'orgName'  => 'boo_firstname',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            ),
            array(
                'name'     => 'name',
                'orgName'  => 'boo_name',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Robez-Masson'
            ),
            array(
                'name'     => 'otherColumn',
                'orgName'  => 'boo_other_column',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Happy Face'
            )
        );

        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ));

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Entity\Hydrator(
                $services->get('MetadataRepository'),
                $services->get('UnitOfWork')
            ))
            ->then($data = $hydrator->hydrate($data))
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data['db__table']->otherColumn)
                ->isIdenticalTo('Happy Face');
    }

    public function testHydrateShouldHydrateIntoDbTable()
    {

        $services = new \CCMBenchmark\Ting\Services();

        $data = array(
            array(
                'name'     => 'fname',
                'orgName'  => 'boo_firstname',
                'table'    => '',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            ),
            array(
                'name'     => 'name',
                'orgName'  => 'boo_name',
                'table'    => '',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Robez-Masson'
            )
        );

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Entity\Hydrator(
                $services->get('MetadataRepository'),
                $services->get('UnitOfWork')
            ))
            ->then($data = $hydrator->hydrate($data))
            ->string($data['db__table']->name)
                ->isIdenticalTo('Robez-Masson')
            ->string($data['db__table']->fname)
                ->isIdenticalTo('Sylvain');
    }
}
