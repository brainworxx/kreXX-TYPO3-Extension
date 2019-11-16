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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Code;

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;

class CodegenTest extends AbstractTest
{
    const FIRST_RUN = 'firstRun';
    const GET_CONNECTOR_LEFT = 'getConnectorLeft';
    const GET_CONNECTOR_RIGHT = 'getConnectorRight';
    const CONCATENATED_CONNECTORS = 'getConnectorLeftnamegetConnectorRight';

    /**
     * Our test subject
     *
     * @var Codegen
     */
    protected $codegenHandler;

    /**
     * The model for the code generation.
     *
     * @var \Brainworxx\Krexx\Analyse\Model
     */
    protected $fixture;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $connectorMock;

    public function setUp()
    {
        parent::setUp();

        $this->codegenHandler = new Codegen(Krexx::$pool);
        $this->codegenHandler->setAllowCodegen(true);
        $this->setValueByReflection(static::FIRST_RUN, false, $this->codegenHandler);

        $this->fixture = new Model(Krexx::$pool);
        $this->fixture->setName('name')
            ->setIsPublic(false);

        // Mock the connector service and inject it into the model.
        $this->connectorMock = $this->createMock(Connectors::class);
        $this->setValueByReflection('connectorService', $this->connectorMock, $this->fixture);
    }

    /**
     * Add the expects calls to the already injected connector mock.
     *
     * @param integer $left
     * @param integer $right
     */
    protected function expectConnectorCalls($left, $right)
    {
        $this->connectorMock->expects($this->exactly($left))
            ->method(static::GET_CONNECTOR_LEFT)
            ->will($this->returnValue(static::GET_CONNECTOR_LEFT));
        $this->connectorMock->expects($this->exactly($right))
            ->method(static::GET_CONNECTOR_RIGHT)
            ->will($this->returnValue(static::GET_CONNECTOR_RIGHT));
    }

    /**
     * Test the pool handling.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::__construct
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->codegenHandler));
        $this->assertEquals($this->codegenHandler, Krexx::$pool->codegenHandler);
    }

    /**
     * Test the forbidden code generation.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     */
    public function testGenerateSourceNoGen()
    {
        $this->codegenHandler->setAllowCodegen(false);
        $this->expectConnectorCalls(0, 0);

        $this->assertEquals('. . .', $this->codegenHandler->generateSource($this->fixture));
    }

    /**
     * Test the concatenation of the first run.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceFirstRun()
    {
        $this->setValueByReflection(static::FIRST_RUN, true, $this->codegenHandler);
        $this->expectConnectorCalls(1, 1);

        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );

        // It's not the first run anymore.
        $this->assertEquals(false, $this->retrieveValueByReflection(static::FIRST_RUN, $this->codegenHandler));
    }

    /**
     * Test the stop return, oin case of constants.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     */
    public function testGenerateSourceMetaConstants()
    {
        $this->expectConnectorCalls(0, 0);
        $this->fixture->setIsMetaConstants(true);
        $this->assertEquals(
            ';stop;',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test an empty return value, in case of empty connectors.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     */
    public function testGenerateSourceEmpty()
    {
        $this->connectorMock->expects($this->exactly(1))
            ->method(static::GET_CONNECTOR_LEFT)
            ->will($this->returnValue(''));
        $this->connectorMock->expects($this->exactly(1))
            ->method(static::GET_CONNECTOR_RIGHT)
            ->will($this->returnValue(''));

        $this->assertEquals(
            '',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of debug methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIsDebug()
    {
        $this->expectConnectorCalls(2, 2);
        $this->fixture->setType($this->codegenHandler::TYPE_DEBUG_METHOD);
        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of debug methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIteratorToArray()
    {
        $this->expectConnectorCalls(2, 2);
        $this->fixture->setMultiLineCodeGen($this->codegenHandler::ITERATOR_TO_ARRAY);
        $this->assertEquals(
            'iterator_to_array(;firstMarker;)getConnectorLeftnamegetConnectorRight',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the coegeneration for unaccessible array values.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     */
    public function testGenerateSourceArrayValueAccess()
    {
        $this->expectConnectorCalls(1, 1);
        $this->connectorMock->expects($this->once())
            ->method('setParameters')
            ->with('0');
        $this->connectorMock->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue('0'));

        $this->fixture
            ->setMultiLineCodeGen($this->codegenHandler::ARRAY_VALUES_ACCESS)
            ->setConnectorParameters('0');
        $this->assertEquals(
            'array_values(;firstMarker;)[0]',
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case of public access.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceIsPublic()
    {
        $this->expectConnectorCalls(2, 2);
        $this->fixture->setIsPublic(true);
        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the concatenation in case that the model is in the scope.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::concatenation
     */
    public function testGenerateSourceInScope()
    {
        $this->expectConnectorCalls(2, 2);

        // Create the scope mock and inject it.
        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('testModelForCodegen')
            ->with($this->fixture)
            ->will($this->returnValue(true));
        Krexx::$pool->scope = $scopeMock;

        $this->assertEquals(
            static::CONCATENATED_CONNECTORS,
            $this->codegenHandler->generateSource($this->fixture)
        );
    }

    /**
     * Test the '. . .' when out of scope.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateSource
     */
    public function testGenerateSourceNotInScope()
    {
        $this->expectConnectorCalls(1, 1);

        // Create the scope mock and inject it.
        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('testModelForCodegen')
            ->with($this->fixture)
            ->will($this->returnValue(false));
        Krexx::$pool->scope = $scopeMock;

        $this->assertEquals('. . .', $this->codegenHandler->generateSource($this->fixture));
    }

    /**
     * Test the small ones.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateWrapperLeft
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::generateWrapperRight
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::setAllowCodegen
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::getAllowCodegen
     */
    public function testSimpleGetterandSetter()
    {
        $this->assertEquals('', $this->codegenHandler->generateWrapperLeft());
        $this->assertEquals('', $this->codegenHandler->generateWrapperRight());
        // This is set during the setUp
        $this->assertEquals(true, $this->codegenHandler->getAllowCodegen());
        $this->assertEquals(true, $this->codegenHandler->getAllowCodegen());
    }

    /**
     * Test the parameter analysis, with a default parameter
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::parameterToString
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     */
    public function testParameterToStringWithDefaultPhpFive()
    {
        // Create a mock with some supply data.
        $refParamMock = $this->createMock(\ReflectionParameter::class);
        $refParamMock->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('Parameter #8 [ <required> Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection $wahtever = \'<h1>Default Stuff</h...\' ]'));
        $refParamMock->expects($this->once())
            ->method('isDefaultValueAvailable')
            ->will($this->returnValue(true));
        $refParamMock->expects($this->once())
            ->method('getDefaultValue')
            ->will($this->returnValue('<h1>Default Stuff</h1>'));
        $refParamMock->expects($this->once())
            ->method('isPassedByReference')
            ->will($this->returnValue(false));
        $refParamMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('wahtever'));

        $this->assertEquals(
            'Brainworxx\Krexx\Analyse\Callback\Analyse\ConfigSection $wahtever = \'&lt;h1&gt;Default Stuff&lt;/h1&gt;\'',
            $this->codegenHandler->parameterToString($refParamMock)
        );
    }

    /**
     * Test the parameter analysis, with a required parameter.
     * We use a speciaql DetTime parameter as a fixture.
     *
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::parameterToString
     * @covers \Brainworxx\Krexx\Analyse\Code\Codegen::translateDefaultValue
     */
    public function testParameterToStringWithRequiredPhpSeven()
    {
        // Create a mock with some supply data.
        $refParamMock = $this->createMock(\ReflectionParameter::class);
        $refParamMock->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('Parameter #2 [ <required> DateTimeZone $object ]'));
        $refParamMock->expects($this->once())
            ->method('isDefaultValueAvailable')
            ->will($this->returnValue(false));
        $refParamMock->expects($this->never())
            ->method('getDefaultValue');
        $refParamMock->expects($this->once())
            ->method('isPassedByReference')
            ->will($this->returnValue(false));
        $refParamMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('object'));

        $this->assertEquals(
            'DateTimeZone $object',
            $this->codegenHandler->parameterToString($refParamMock)
        );
    }
}
