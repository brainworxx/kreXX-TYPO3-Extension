<?php
/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Tests\Analyse;

use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class ModelTest extends AbstractTest
{

    /**
     * A fresh instance of the model, redy to use.
     *
     * @var Model
     */
    protected $model;

    /**
     * Getting a fresh reflection of the model.
     *
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public function setUp()
    {
        parent::setUp();
        $this->model = new Model(\Krexx::$pool);
    }

    /**
     * Unsetting the reflection.
     *
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->modelRef);
    }

    /**
     * Testing the setter for the data value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setData
     */
    public function testSetData()
    {
        $data = new \stdClass();
        $this->assertEquals($this->model, $this->model->setData($data));
        $this->assertAttributeEquals($data, 'data', $this->model);
    }

    /**
     * Testing the getter of the data value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getData
     */
    public function testGetData()
    {
        $data = new \stdClass();
        $this->setValueByReflection('data', $data, $this->model);
        $this->assertEquals($data, $this->model->getData());
    }

    /**
     * Testing the setter for the name value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setName
     */
    public function testSetName()
    {
        $data = 'some name';
        $this->assertEquals($this->model, $this->model->setName($data));
        $this->assertAttributeEquals($data, 'name', $this->model);
    }

    /**
     * Testing the getter for the name value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getName
     */
    public function testGetName()
    {
        $data = 'some name';
        $this->setValueByReflection('name', $data, $this->model);
        $this->assertEquals($data, $this->model->getName());
    }

    /**
     * Testing the setter for the normal value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setNormal
     */
    public function testSetNormal()
    {
        $data = 'some normal';
        $this->assertEquals($this->model, $this->model->setNormal($data));
        $this->assertAttributeEquals($data, 'normal', $this->model);
    }

    /**
     * Testing the getter for the normal value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getNormal
     */
    public function testGetNormal()
    {
        $data = 'some normal';
        $this->setValueByReflection('normal', $data, $this->model);
        $this->assertEquals($data, $this->model->getNormal());
    }

    /**
     * Testing the setter for the additional value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setAdditional
     */
    public function testSetAdditional()
    {
        $data = 'some additional';
        $this->assertEquals($this->model, $this->model->setAdditional($data));
        $this->assertAttributeEquals($data, 'additional', $this->model);
    }

    /**
     * Testing the getter for the additional value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getAdditional
     */
    public function testGetAdditional()
    {
        $data = 'some additional';
        $this->setValueByReflection('additional', $data, $this->model);
        $this->assertEquals($data, $this->model->getAdditional());
    }

    /**
     * Testing the setter for the type value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setType
     */
    public function testSetType()
    {
        $data = 'some type';
        $this->assertEquals($this->model, $this->model->setType($data));
        $this->assertAttributeEquals($data, 'type', $this->model);
    }

    /**
     * Testing the getter for the type value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getType
     */
    public function testGetType()
    {
        $data = 'some type';
        $this->setValueByReflection('type', $data, $this->model);
        $this->assertEquals($data, $this->model->getType());
    }

    /**
     * Testing the getter for the left connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorLeft
     */
    public function testGetConnectorLeft()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorLeft')
            ->will($this->returnValue($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorLeft());
    }

    /**
     * Testing the right connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorRight
     */
    public function testGetConnectorRight()
    {
        $data = 'some value';
        $cap = 5;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorRight')
            ->will($this->returnValue($data))
            ->with($this->equalTo($cap));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorRight($cap));
    }

    /**
     * Testing the setter of the dom id.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setDomid
     */
    public function testSetDomid()
    {
        $data = 'some value';

        $this->assertEquals($this->model, $this->model->setDomid($data));
        $this->assertAttributeEquals($data, 'domid', $this->model);
    }

    /**
     * Testing the getter of the dom id.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getDomid
     */
    public function testGetDomid()
    {
        $data = 'some value';

        $this->setValueByReflection('domid', $data, $this->model);
        $this->assertEquals($data, $this->model->getDomid());
    }

    /**
     * Testing the getter for the extras boolean.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getHasExtra
     */
    public function testGetHasExtra()
    {
        $data = true;

        $this->setValueByReflection('hasExtra', $data, $this->model);
        $this->assertEquals($data, $this->model->getHasExtra());
    }

    /**
     * Testing the setter for the extras boolean.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setHasExtra
     */
    public function testSetHasExtra()
    {
        $data = true;

        $this->assertEquals($this->model, $this->model->setHasExtra($data));
        $this->assertAttributeEquals($data, 'hasExtra', $this->model);
    }

    /**
     * Testing the getter for the multiline code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getMultiLineCodeGen
     */
    public function testGetMultiLineCodeGen()
    {
        $data = 'some value';

        $this->setValueByReflection('multiLineCodeGen', $data, $this->model);
        $this->assertEquals($data, $this->model->getMultiLineCodeGen());
    }

    /**
     * Testing the setter for the multiline code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setMultiLineCodeGen
     */
    public function testSetMultiLineCodeGen()
    {
        $data = 'some value';

        $this->assertEquals($this->model, $this->model->setMultiLineCodeGen($data));
        $this->assertAttributeEquals($data, 'multiLineCodeGen', $this->model);
    }

    /**
     * Testing the getter of the isCallback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getIsCallback
     */
    public function testGetIsCallback()
    {
        $data = true;

        $this->setValueByReflection('isCallback', $data, $this->model);
        $this->assertEquals($data, $this->model->getIsCallback());
    }

    /**
     * Testing tje setter of the isCallback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setIsCallback
     */
    public function testSetIsCallback()
    {
        $data = true;

        $this->assertEquals($this->model, $this->model->setIsCallback($data));
        $this->assertAttributeEquals($data, 'isCallback', $this->model);
    }

    /**
     * Testing the setter for the connector parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setConnectorParameters
     */
    public function testSetConnectorParameters()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setParameters')
            ->will($this->returnValue($data))
            ->with($this->equalTo($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorParameters($data));
    }

    /**
     * Testing the getter for the connector parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorParameters
     */
    public function testGetConnectorParameters()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorParameters());
    }

    /**
     * Testing the setter of the connector type
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setConnectorType
     */
    public function testSetConnectorType()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setType')
            ->will($this->returnValue($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorType($data));
    }

    /**
     * Testing the setter of the custom connector left.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setCustomConnectorLeft
     */
    public function testSetCustomConnectorLeft()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setCustomConnectorLeft')
            ->will($this->returnValue($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setCustomConnectorLeft($data));
    }

    /**
     * Testing the getter of the language connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorLanguage
     */
    public function testGetConnectorLanguage()
    {
        $data = 'some value';

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getLanguage')
            ->will($this->returnValue($data));

        $this->setValueByReflection('connectorService', $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorLanguage());
    }

    /**
     * Testing the getter of the parameter fo the callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getParameters
     */
    public function testGetParameters()
    {
        $data = array(
            'param1' => 'value1',
            'param2' => 'value2',
        );

        $this->setValueByReflection('parameters', $data, $this->model);
        $this->assertEquals($data, $this->model->getParameters());
    }

    /**
     * Testing the getter of the isMetaConstants
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getIsMetaConstants
     */
    public function testGetIsMetaConstants()
    {
        $data = true;

        $this->setValueByReflection('isMetaConstants', $data, $this->model);
        $this->assertEquals($data, $this->model->getIsMetaConstants());
    }

    /**
     * Testing the setter for the isMetaConstants.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setIsMetaConstants
     */
    public function testSetIsMetaConstants()
    {
        $data = true;

        $this->assertEquals($this->model, $this->model->setIsMetaConstants($data));
        $this->assertAttributeEquals($data, 'isMetaConstants', $this->model);
    }

    /**
     * Testing the setting of the isPublic.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setIsPublic
     */
    public function testSetIsPublic()
    {
        $data = true;

        $this->assertEquals($this->model, $this->model->setIsPublic($data));
        $this->assertAttributeEquals($data, 'isPublic', $this->model);
    }

    /**
     * Testing the getting of the isPublic.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getIsPublic
     */
    public function testGetIsPublic()
    {
        $data = true;

        $this->setValueByReflection('isPublic', $data, $this->model);
        $this->assertEquals($data, $this->model->getIsPublic());
    }
}
