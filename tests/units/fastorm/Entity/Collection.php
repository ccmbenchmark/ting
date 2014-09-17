<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class Collection extends atoum
{

    public function testHydrateNullShouldReturnNull()
    {
        $this
            ->if($collection = new \fastorm\Entity\Collection())
            ->variable($collection->hydrate(null))
                ->isNull();
    }

    public function testHydrateShouldDoNothingWithoutHydrator()
    {
        $data = array('Bouh' => array());

        $this
            ->if($collection = new \fastorm\Entity\Collection())
            ->array($collection->hydrate($data))
                ->isIdenticalTo($data);
    }

    public function testHydrateWithHydratorShouldCallHydratorHydrate()
    {
        $services     = new \fastorm\Services();
        $mockHydrator = new \mock\fastorm\Entity\Hydrator(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork')
        );

        $data = array(
            array(
                'name'     => 'name',
                'orgName'  => 'BOO_NAME',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            )
        );

        $this
            ->if($collection = new \fastorm\Entity\Collection())
            ->then($collection->hydrator($mockHydrator))
            ->then($collection->hydrate($data))
            ->mock($mockHydrator)
                ->call('hydrate')
                    ->withIdenticalArguments($data)->once();
    }

    public function testIterator()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['value'], ['value2']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new \mock\fastorm\Driver\Mysqli\Result($mockMysqliResult);

        $this
            ->if($collection = new \fastorm\Entity\Collection())
            ->then($collection->set($result))
            ->then($collection->rewind())
            ->mock($result)
                ->call('rewind')->once()
                ->call('next')->once()
            ->then($collection->key())
            ->mock($result)
                ->call('key')->once()
            ->then($collection->next())
            ->mock($result)
                ->call('next')->twice()
            ->then($collection->valid())
            ->mock($result)
                ->call('valid')->once()
            ->then($collection->current())
            ->mock($result)
                ->call('current')->once();
    }
}