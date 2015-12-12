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

namespace tests\units\CCMBenchmark\Ting\Repository;

use mageekguy\atoum;

class HydratorArray extends atoum
{
    public function testHydrateShouldReturnArray()
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
        $collection = $services->get('CollectionFactory')->get();

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorArray())
            ->then($bouh = $hydrator->hydrate('connectionName', 'database', $data, $collection))
            ->array($bouh)
                ->isIdenticalTo(['fname' => 'Sylvain', 'name' => 'Robez-Masson']);
    }
}
