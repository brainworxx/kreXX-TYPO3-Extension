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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use stdClass;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\View\Messages;

class ModelTest extends AbstractTest
{
    const SOME_STRING_TO_PASS_THROUGH = 'some string to pass through';
    const CONNECTOR_SERVICE = 'connectorService';
    const SET_PARAMETERS = 'setParameters';

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
     */
    protected function krexxUp()
    {
        parent::krexxUp();
        $this->model = new Model(Krexx::$pool);
    }

    /**
     * Test if we get the pool as well as the connector service.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::__construct
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->model));

        $this->assertInstanceOf(
            Connectors::class,
            $this->retrieveValueByReflection('connectorService', $this->model)
        );
    }

    /**
     * Test if the callback gets set.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::injectCallback
     */
    public function testInjectCallback()
    {
        $mockCallback = $this->createMock(
            AbstractCallback::class
        );

        $mockCallback->expects($this->never())
            ->method('callMe')
            ->will($this->returnValue(null));

        $mockCallback->expects($this->never())
            ->method(static::SET_PARAMETERS)
            ->will($this->returnValue(null));

        $this->assertEquals($this->model, $this->model->injectCallback($mockCallback));

        $this->assertEquals($mockCallback, $this->retrieveValueByReflection('callback', $this->model));
    }

    /**
     * The rendering will call the callback. We will mock the callback and test
     * if we get the actual output from it.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::renderMe
     */
    public function testRenderMe()
    {
        $mockCallback = $this->createMock(
            AbstractCallback::class
        );

        $htmlResult = 'Rendered HTML';

        // The callback should return the HTML result, which gets mocked here.
        $mockCallback->expects($this->once())
            ->method('callMe')
            ->will($this->returnValue($htmlResult));

        $mockCallback->expects($this->once())
            ->method(static::SET_PARAMETERS)
            ->will($this->returnValue($mockCallback));

        $this->assertEquals('', $this->model->renderMe(), 'No callback, no HTML.');

        // Test id the HTML result gates returned and both methods gets called once.
        $this->assertEquals($htmlResult, $this->model->injectCallback($mockCallback)->renderMe());
    }

    /**
     * Test if we can add several parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::addParameter
     */
    public function testAddParameter()
    {
        $parameterOne = new stdClass();
        $parameterTwo = "some value";

        $this->model->addParameter('parameterOne', $parameterOne);
        $this->model->addParameter('parameterTwo', $parameterTwo);

        $expectedResult = [
            'parameterOne' => $parameterOne,
            'parameterTwo' => $parameterTwo,
        ];

        $this->assertEquals($expectedResult, $this->model->getParameters());
    }

    /**
     * Test the setting of the help id. The help id sets additional text to the
     * specific analysis, to explain the output.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setHelpid
     */
    public function testSetHelpId()
    {
        // Mock the message class, which will provide the help text.
        $helpText = 'some help text';
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('getHelp')
            ->will($this->returnValue($helpText));
        Krexx::$pool->messages = $messageMock;

        // Test the return value for chaining
        $this->assertEquals($this->model, $this->model->setHelpid('some id'));

        // Test if the $helpText got set inside the json.
        $this->assertEquals(['Help' => $helpText], $this->model->getJson());
    }

    /**
     * Test if we can add stuff to the json. Linebreaks should be removed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::addToJson
     */
    public function testAddToJson()
    {
        $text = "Look\n at\r me\n\r, I'm\n\r a string";
        $key = 'some key';
        $expected = [
            $key => "Look at me, I'm a string"
        ];

        // Set the value.
        $this->assertEquals($this->model, $this->model->addToJson($key, $text));
        $this->assertEquals($expected, $this->model->getJson());

        //Remove the value. Should be empty now.
        $this->assertEquals($this->model, $this->model->addToJson($key, ''));
        $this->assertEquals([], $this->model->getJson());
    }

    /**
     * Test the getter for the json value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getJson
     */
    public function testGetJson()
    {
        $jsonData = [
            'some' => 'value',
            'to' => 'check',
        ];

        // Set it via reflections.
        $this->setValueByReflection('json', $jsonData, $this->model);
        $this->assertEquals($jsonData, $this->model->getJson());
    }

    /**
     * Testing the setter for the data value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setData
     */
    public function testSetData()
    {
        $data = new stdClass();
        $this->assertEquals($this->model, $this->model->setData($data));
        $this->assertEquals($data, $this->model->getData());
    }

    /**
     * Testing the getter of the data value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getData
     */
    public function testGetData()
    {
        $data = new stdClass();
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
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setName($data));
        $this->assertEquals($data, $this->model->getName());
    }

    /**
     * Testing the getter for the name value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getName
     */
    public function testGetName()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
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
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setNormal($data));
        $this->assertEquals($data, $this->model->getNormal());
    }

    /**
     * Testing the getter for the normal value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getNormal
     */
    public function testGetNormal()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
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
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setAdditional($data));
        $this->assertEquals($data, $this->model->getAdditional());
    }

    /**
     * Testing the getter for the additional value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getAdditional
     */
    public function testGetAdditional()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
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
        $this->assertEquals($data, $this->model->getType());
    }

    /**
     * Testing the getter for the type value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getType
     */
    public function testGetType()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
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
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorLeft')
            ->will($this->returnValue($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorLeft());
    }

    /**
     * Testing the right connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorRight
     */
    public function testGetConnectorRight()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $cap = 5;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorRight')
            ->will($this->returnValue($data))
            ->with($this->equalTo($cap));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorRight($cap));
    }

    /**
     * Testing the setter of the dom id.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setDomid
     */
    public function testSetDomid()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $this->assertEquals($this->model, $this->model->setDomid($data));
        $this->assertEquals($data, $this->model->getDomid());
    }

    /**
     * Testing the getter of the dom id.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getDomid
     */
    public function testGetDomid()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $this->setValueByReflection('domid', $data, $this->model);
        $this->assertEquals($data, $this->model->getDomid());
    }

    /**
     * Testing the getter for the extras boolean.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::hasExtra
     */
    public function testGetHasExtra()
    {
        $data = true;

        $this->setValueByReflection('hasExtra', $data, $this->model);
        $this->assertEquals($data, $this->model->hasExtra());
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
        $this->assertEquals($data, $this->model->hasExtra());
    }

    /**
     * Testing the setter for the connector parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setConnectorParameters
     */
    public function testSetConnectorParameters()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method(static::SET_PARAMETERS)
            ->will($this->returnValue($data))
            ->with($this->equalTo($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorParameters($data));
    }

    /**
     * Testing the getter for the connector parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorParameters
     */
    public function testGetConnectorParameters()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorParameters());
    }

    /**
     * Testing the setter of the connector type
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setConnectorType
     */
    public function testSetConnectorType()
    {
        $data = 1234;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setType')
            ->will($this->returnValue($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorType($data));
    }

    /**
     * Testing the setter of the custom connector left.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setCustomConnectorLeft
     */
    public function testSetCustomConnectorLeft()
    {
        $data = 5678;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setCustomConnectorLeft')
            ->will($this->returnValue($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setCustomConnectorLeft($data));
    }

    /**
     * Testing the getter of the language connector.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getConnectorLanguage
     */
    public function testGetConnectorLanguage()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getLanguage')
            ->will($this->returnValue($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
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
     * Testing the setting of the code generation type.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setCodeGenType
     */
    public function testSetCodeGenType()
    {
        $data = 'some constant';

        $this->assertEquals($this->model, $this->model->setCodeGenType($data));
        $this->model->setCodeGenType('');
        $this->assertEquals($data, $this->model->getCodeGenType(), 'The value must not get overwritten');
    }

    /**
     * Testing the getter of the code generation type.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::getCodeGenType
     */
    public function testGetCodeGenType()
    {
        $data = 'Another constant';

        $this->assertEquals(
            Codegen::CODEGEN_TYPE_EMPTY,
            $this->model->getCodeGenType(),
            'Nothing was set so far. Fallback to empty.'
        );

        $this->model->setConnectorType(Connectors::CONNECTOR_METHOD);
        $this->assertEquals(
            '',
            $this->model->getCodeGenType(),
            'With some sort of connectors, give back the empty string.'
        );

        $this->model->setCodeGenType($data);
        $this->assertEquals($data, $this->model->getCodeGenType(), 'Standard handling.');
    }

    /**
     * Test if we are handling a callback or stuff with "extra".
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::isExpandable
     */
    public function testIsExpandable()
    {
        $this->assertFalse($this->model->isExpandable());

        $this->model->setHasExtra(true);
        $this->assertTrue($this->model->isExpandable());

        $callBack = new CallbackCounter(\Krexx::$pool);
        $this->model->injectCallback($callBack);
        $this->model->setHasExtra(false);
        $this->assertTrue($this->model->isExpandable());

        $this->model->setHasExtra(true);
        $this->assertTrue($this->model->isExpandable());
    }

    /**
     * Test the setter/getter for the return type.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setReturnType
     * @covers \Brainworxx\Krexx\Analyse\Model::getReturnType
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::getReturnType
     * @covers \Brainworxx\Krexx\Analyse\Code\Connectors::setReturnType
     */
    public function testSetGetReturnType()
    {
        $data = 'string';
        $this->assertEquals($this->model, $this->model->setReturnType($data));
        $this->assertEquals($data, $this->model->getReturnType(), 'Get of it out what you put in.');
    }

    /**
     * Test the setter/getter for the key type.
     *
     * @covers \Brainworxx\Krexx\Analyse\Model::setKeyType
     * @covers \Brainworxx\Krexx\Analyse\Model::getKeyType
     */
    public function testSetGetKeyType()
    {
        $data = 'just a value';
        $this->assertEquals($this->model, $this->model->setKeyType($data));
        $this->assertEquals($data, $this->model->getKeyType(), 'Get of it out what you put in.');
    }
}
