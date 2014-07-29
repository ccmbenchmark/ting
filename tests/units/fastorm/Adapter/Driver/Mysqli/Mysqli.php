<?php

namespace tests\units\fastorm\Adapter\Driver\Mysqli;

use \mageekguy\atoum;

class Mysqli extends atoum
{

    public function testProtectTableNameProtectionWithoutDatabase()
    {
        $this
            ->if($object = new \fastorm\Adapter\Driver\Mysqli\Mysqli())
            ->variable($object->protectTableName('table'))
                ->isEqualTo('`table`');
    }

    public function testProtectTableNameProtectionWithDatabase()
    {
        $this
            ->if($object = new \fastorm\Adapter\Driver\Mysqli\Mysqli())
            ->variable($object->protectTableName('database.table'))
                ->isEqualTo('`database`.`table`');
    }
}
