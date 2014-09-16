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

        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
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
            ->if($hydrator = new \fastorm\Entity\Hydrator(
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

        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
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
            ->if($hydrator = new \fastorm\Entity\Hydrator(
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

        $services = new \fastorm\Services();

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
            ->if($hydrator = new \fastorm\Entity\Hydrator(
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
