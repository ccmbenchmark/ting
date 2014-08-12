<?php

namespace tests\units\fastorm\Entity;

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

        $metadata = new \fastorm\Entity\Metadata();
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

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $this
            ->if($hydrator = new \fastorm\Entity\Hydrator($metadataRepository))
            ->then($data = $hydrator->hydrate($data))
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateShouldHydrateIntoDbTable()
    {
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

        $metadata = new \fastorm\Entity\Metadata();
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

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $this
            ->if($hydrator = new \fastorm\Entity\Hydrator($metadataRepository))
            ->then($data = $hydrator->hydrate($data))
            ->string($data['db__table']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['db__table']->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateShouldHydrateWithDbPrefix()
    {
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
            ->if($hydrator = new \fastorm\Entity\Hydrator())
            ->then($data = $hydrator->hydrate($data))
            ->string($data['db__table']->db__name)
                ->isIdenticalTo('Robez-Masson')
            ->string($data['db__table']->db__fname)
                ->isIdenticalTo('Sylvain');
    }
}
