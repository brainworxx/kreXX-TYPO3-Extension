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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use PHPUnit\Framework\Attributes\CoversMethod;
use stdClass;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\View\Messages;

#[CoversMethod(Model::class, '__construct')]
#[CoversMethod(Model::class, 'injectCallback')]
#[CoversMethod(Model::class, 'renderMe')]
#[CoversMethod(Model::class, 'addParameter')]
#[CoversMethod(Model::class, 'setHelpid')]
#[CoversMethod(Model::class, 'addToJson')]
#[CoversMethod(Model::class, 'getJson')]
#[CoversMethod(Model::class, 'setData')]
#[CoversMethod(Model::class, 'getData')]
#[CoversMethod(Model::class, 'setName')]
#[CoversMethod(Model::class, 'getName')]
#[CoversMethod(Model::class, 'setNormal')]
#[CoversMethod(Model::class, 'getNormal')]
#[CoversMethod(Model::class, 'setAdditional')]
#[CoversMethod(Model::class, 'getAdditional')]
#[CoversMethod(Model::class, 'setType')]
#[CoversMethod(Model::class, 'getType')]
#[CoversMethod(Model::class, 'getConnectorLeft')]
#[CoversMethod(Model::class, 'getConnectorRight')]
#[CoversMethod(Model::class, 'setDomid')]
#[CoversMethod(Model::class, 'getDomid')]
#[CoversMethod(Model::class, 'hasExtra')]
#[CoversMethod(Model::class, 'setHasExtra')]
#[CoversMethod(Model::class, 'setConnectorParameters')]
#[CoversMethod(Model::class, 'getConnectorParameters')]
#[CoversMethod(Model::class, 'setConnectorType')]
#[CoversMethod(Model::class, 'setCustomConnectorLeft')]
#[CoversMethod(Model::class, 'getConnectorLanguage')]
#[CoversMethod(Model::class, 'getParameters')]
#[CoversMethod(Model::class, 'setCodeGenType')]
#[CoversMethod(Model::class, 'getCodeGenType')]
#[CoversMethod(Model::class, 'isExpandable')]
#[CoversMethod(Model::class, 'setReturnType')]
#[CoversMethod(Model::class, 'getReturnType')]
#[CoversMethod(Connectors::class, 'getReturnType')]
#[CoversMethod(Connectors::class, 'setReturnType')]
class ModelTest extends AbstractHelper
{
    public const  SOME_STRING_TO_PASS_THROUGH = 'some string to pass through';
    public const  CONNECTOR_SERVICE = 'connectorService';
    public const  SET_PARAMETERS = 'setParameters';

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
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new Model(Krexx::$pool);
    }

    /**
     * Test if we get the pool as well as the connector service.
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
     */
    public function testInjectCallback()
    {
        $mockCallback = $this->createMock(
            AbstractCallback::class
        );

        $mockCallback->expects($this->never())
            ->method('callMe');

        $mockCallback->expects($this->never())
            ->method(static::SET_PARAMETERS);

        $this->assertEquals($this->model, $this->model->injectCallback($mockCallback));

        $this->assertEquals($mockCallback, $this->retrieveValueByReflection('callback', $this->model));
    }

    /**
     * The rendering will call the callback. We will mock the callback and test
     * if we get the actual output from it.
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
            ->willReturn($htmlResult);

        $mockCallback->expects($this->once())
            ->method(static::SET_PARAMETERS)
            ->willReturn($mockCallback);

        $this->assertEquals('', $this->model->renderMe(), 'No callback, no HTML.');

        // Test id the HTML result gates returned and both methods gets called once.
        $this->assertEquals($htmlResult, $this->model->injectCallback($mockCallback)->renderMe());
    }

    /**
     * Test if we can add several parameters.
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
     */
    public function testSetHelpId()
    {
        // Mock the message class, which will provide the help text.
        $helpText = 'some help text';
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->exactly(2))
            ->method('getHelp')
            ->with(...$this->withConsecutive(['metaHelp'], ['some id']))
            ->willReturnMap(
                [
                    ['metaHelp', [], 'Help'],
                    ['some id', [], $helpText]
                ]
            );
        Krexx::$pool->messages = $messageMock;

        // Test the return value for chaining
        $this->assertEquals($this->model, $this->model->setHelpid('some id'));

        // Test if the $helpText got set inside the json.
        $this->assertEquals(['Help' => $helpText], $this->model->getJson());
    }

    /**
     * Test if we can add stuff to the json. Linebreaks should be removed.
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
     */
    public function testSetData()
    {
        $data = new stdClass();
        $this->assertEquals($this->model, $this->model->setData($data));
        $this->assertEquals($data, $this->model->getData());
    }

    /**
     * Testing the getter of the data value.
     */
    public function testGetData()
    {
        $data = new stdClass();
        $this->setValueByReflection('data', $data, $this->model);
        $this->assertEquals($data, $this->model->getData());
    }

    /**
     * Testing the setter for the name value.
     */
    public function testSetName()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setName($data));
        $this->assertEquals($data, $this->model->getName());
    }

    /**
     * Testing the getter for the name value.
     */
    public function testGetName()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->setValueByReflection('name', $data, $this->model);
        $this->assertEquals($data, $this->model->getName());
    }

    /**
     * Testing the setter for the normal value.
     */
    public function testSetNormal()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setNormal($data));
        $this->assertEquals($data, $this->model->getNormal());
    }

    /**
     * Testing the getter for the normal value.
     */
    public function testGetNormal()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->setValueByReflection('normal', $data, $this->model);
        $this->assertEquals($data, $this->model->getNormal());
    }

    /**
     * Testing the setter for the additional value.
     */
    public function testSetAdditional()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->assertEquals($this->model, $this->model->setAdditional($data));
        $this->assertEquals($data, $this->model->getAdditional());
    }

    /**
     * Testing the getter for the additional value.
     */
    public function testGetAdditional()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->setValueByReflection('additional', $data, $this->model);
        $this->assertEquals($data, $this->model->getAdditional());
    }

    /**
     * Testing the setter for the type value.
     */
    public function testSetType()
    {
        $data = 'some type';
        $this->assertEquals($this->model, $this->model->setType($data));
        $this->assertEquals($data, $this->model->getType());
    }

    /**
     * Testing the getter for the type value.
     */
    public function testGetType()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $this->setValueByReflection('type', $data, $this->model);
        $this->assertEquals($data, $this->model->getType());
    }

    /**
     * Testing the getter for the left connector.
     */
    public function testGetConnectorLeft()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorLeft')
            ->willReturn($data);

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorLeft());
    }

    /**
     * Testing the right connector.
     */
    public function testGetConnectorRight()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;
        $cap = 5;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getConnectorRight')
            ->willReturn($data)
            ->with($this->equalTo($cap));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorRight($cap));
    }

    /**
     * Testing the setter of the dom id.
     */
    public function testSetDomid()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $this->assertEquals($this->model, $this->model->setDomid($data));
        $this->assertEquals($data, $this->model->getDomid());
    }

    /**
     * Testing the getter of the dom id.
     */
    public function testGetDomid()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $this->setValueByReflection('domid', $data, $this->model);
        $this->assertEquals($data, $this->model->getDomid());
    }

    /**
     * Testing the getter for the extras boolean.
     */
    public function testGetHasExtra()
    {
        $data = true;

        $this->setValueByReflection('hasExtra', $data, $this->model);
        $this->assertEquals($data, $this->model->hasExtra());
    }

    /**
     * Testing the setter for the extras boolean.
     */
    public function testSetHasExtra()
    {
        $data = true;

        $this->assertEquals($this->model, $this->model->setHasExtra($data));
        $this->assertEquals($data, $this->model->hasExtra());
    }

    /**
     * Testing the setter for the connector parameters.
     */
    public function testSetConnectorParameters()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method(static::SET_PARAMETERS)
            ->with($this->equalTo($data));

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorParameters($data));
    }

    /**
     * Testing the getter for the connector parameters.
     */
    public function testGetConnectorParameters()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getParameters')
            ->willReturn($data);

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorParameters());
    }

    /**
     * Testing the setter of the connector type
     */
    public function testSetConnectorType()
    {
        $data = 1234;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setType');

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setConnectorType($data));
    }

    /**
     * Testing the setter of the custom connector left.
     */
    public function testSetCustomConnectorLeft()
    {
        $data = 5678;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('setCustomConnectorLeft');

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($this->model, $this->model->setCustomConnectorLeft($data));
    }

    /**
     * Testing the getter of the language connector.
     */
    public function testGetConnectorLanguage()
    {
        $data = static::SOME_STRING_TO_PASS_THROUGH;

        $mockConnector = $this->createMock(Connectors::class);
        $mockConnector->expects($this->once())
            ->method('getLanguage')
            ->willReturn($data);

        $this->setValueByReflection(static::CONNECTOR_SERVICE, $mockConnector, $this->model);
        $this->assertEquals($data, $this->model->getConnectorLanguage());
    }

    /**
     * Testing the getter of the parameter fo the callback.
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
     */
    public function testSetGetReturnType()
    {
        $data = 'string';
        $this->assertEquals($this->model, $this->model->setReturnType($data));
        $this->assertEquals($data, $this->model->getReturnType(), 'Get of it out what you put in.');
    }
}
