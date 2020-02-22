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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

class RoutingTest extends AbstractTest
{
    const ROUTING_MOCK_RETURN_VALUE = 'routing mock success';
    const IS_IN_HIVE = 'isInHive';
    const ADD_TO_HIVE = 'addToHive';
    const NO_ROUTE = 'no routing';

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Routing
     */
    protected $routing;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->routing = new Routing(Krexx::$pool);
        $this->routing->testValue = 123;
        $this->mockEmergencyHandler();
    }

    /**
     * We inject mock routes, to test if they are called, and with what parameter.
     *
     * @param string $allowedRoute
     * @param Model $model
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function mockRouting(string $allowedRoute, Model $model)
    {
        $reflectionClass = new ReflectionClass($this->routing);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $reflectionProperty) {
            if ($reflectionProperty->name === 'pool') {
                continue;
            }
            $className = $reflectionClass->getNamespaceName() . '\\Process\\' . ucfirst($reflectionProperty->name);
            $mock = $this->createMock($className);
            if ($reflectionProperty->name === $allowedRoute) {
                $mock->expects($this->once())
                    ->method('process')
                    ->with($model)
                    ->will($this->returnValue(static::ROUTING_MOCK_RETURN_VALUE));
            } else {
                $mock->expects($this->never())
                    ->method('process');
            }

            $this->setValueByReflection($reflectionProperty->name, $mock, $this->routing);
        }

        return $this->routing->analysisHub($model);
    }

    /**
     * Assert and moch the emergency handler.
     *
     * @param bool $checkEmergencyBreak
     * @param bool $checkNesting
     */
    protected function assertEmergencyHandler(bool $checkEmergencyBreak, bool $checkNesting)
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue($checkEmergencyBreak));
        $emergencyMock->expects($this->once())
            ->method('upOneNestingLevel');
        $emergencyMock->expects($this->once())
            ->method('downOneNestingLevel');
        $emergencyMock->expects($this->once())
            ->method('checkNesting')
            ->will($this->returnValue($checkNesting));
        Krexx::$pool->emergencyHandler = $emergencyMock;
    }

    /**
     * Assert and short circuit the calling of the renderer.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @param string $method
     */
    protected function assertRender(Model $model, string $method)
    {
        $renderMock = $this->createMock(RenderNothing::class);
        $renderMock->expects($this->once())
            ->method($method)
            ->with($model)
            ->will($this->returnValue($method . ' called'));
        Krexx::$pool->render = $renderMock;
    }

    /**
     * Test if all processors will get set, and that the routing class gets
     * set in the pool.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::__construct
     *
     * @throws \ReflectionException
     */
    public function testConstruct()
    {
        $this->assertEquals(123, Krexx::$pool->routing->testValue);

        $reflectionClass = new ReflectionClass($this->routing);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PROTECTED);

        foreach ($properties as $reflectionProperty) {
            if ($reflectionProperty->name === 'pool') {
                continue;
            }
            $this->assertInstanceOf(
                $reflectionClass->getNamespaceName() . '\\Process\\' . ucfirst($reflectionProperty->name),
                $this->retrieveValueByReflection($reflectionProperty->name, $this->routing)
            );
        }
    }

    /**
     * Simply test, if an emergency break gets respected.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubEmergencyBreak()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = true;
        $model->setData($parameter);

        // Make sure to trigger an emergency break.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(true));
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $this->assertEquals('', $this->mockRouting('no route for you', $model));
    }

    /**
     * Simple routing of a string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubString()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = 'some string';
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processString', $model));
    }

    /**
     * Simple routing of an integer.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubInteger()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = 42;
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processInteger', $model));
    }

    /**
     * Simple routing of a null value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubNull()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = null;
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processNull', $model));
    }

    /**
     * Simple routing of a boolean value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubBoolean()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = true;
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processBoolean', $model));
    }

    /**
     * Simple routing of a float value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubFloat()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = 1.234;
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processFloat', $model));
    }

    /**
     * Simple routing of a resource.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubResource()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = curl_init();
        $model->setData($parameter);

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processResource', $model));
    }

    /**
     * What the method name says.
     *
     * @return array
     */
    protected function createArrayParameter()
    {
        return [
            'some', 'values'
        ];
    }

    /**
     * Normal array routing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::preprocessNoneSimpleTypes
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubArrayNormal()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = $this->createArrayParameter();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, false);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method(static::IS_IN_HIVE)
            ->will($this->returnValue(false));
        $recursionMock->expects($this->never())
            ->method(static::ADD_TO_HIVE);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processArray', $model));
    }

    /**
     * Array routing with a nesting problem.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNestedTooDeep
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubArrayNesting()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        $parameter = $this->createArrayParameter();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, true);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->never())
            ->method(static::IS_IN_HIVE);
        $recursionMock->expects($this->never())
            ->method(static::ADD_TO_HIVE);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertRender($model, 'renderSingleChild');
        $this->assertEquals('renderSingleChild called', $this->mockRouting(static::NO_ROUTE, $model));
    }

    /**
     * Array routing with the globals in the hive.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleRecursion
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubGlobalsInHive()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        // We are not really using the globals.
        $parameter = $this->createArrayParameter();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, false);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method(static::IS_IN_HIVE)
            ->will($this->returnValue(true));
        $recursionMock->expects($this->never())
            ->method(static::ADD_TO_HIVE);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertRender($model, 'renderRecursion');
        $this->assertEquals('renderRecursion called', $this->mockRouting(static::NO_ROUTE, $model));
        $this->assertEquals('$GLOBALS', $model->getNormal());
    }

    /**
     * Normal object routing
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::preprocessNoneSimpleTypes
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubObjectNormal()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        // We are not really using the globals.
        $parameter = new stdClass();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, false);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method(static::IS_IN_HIVE)
            ->will($this->returnValue(false));
        $recursionMock->expects($this->once())
            ->method(static::ADD_TO_HIVE)
            ->with($parameter);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processObject', $model));
    }

    /**
     * Object routing with the object in the hive.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::generateDomIdFromObject
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleRecursion
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubObjectInHive()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        // We are not really using the globals.
        $parameter = new stdClass();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, false);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method(static::IS_IN_HIVE)
            ->will($this->returnValue(true));
        $recursionMock->expects($this->never())
            ->method(static::ADD_TO_HIVE);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertRender($model, 'renderRecursion');
        $this->assertEquals('renderRecursion called', $this->mockRouting(static::NO_ROUTE, $model));
        $this->assertNotEmpty($model->getDomid());
    }

    /**
     * Object routing while reaching the nesting level threshold
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNestedTooDeep
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubObjectNesting()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        // We are not really using the globals.
        $parameter = new stdClass();
        $model->setData($parameter);

        $this->assertEmergencyHandler(false, true);
        $this->assertRender($model, 'renderSingleChild');
        $this->assertEquals('renderSingleChild called', $this->mockRouting(static::NO_ROUTE, $model));
    }

    /**
     * Normal closure routing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::analysisHub
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::handleNoneSimpleTypes
     * @covers \Brainworxx\Krexx\Analyse\Routing\Routing::preprocessNoneSimpleTypes
     *
     * @throws \ReflectionException
     */
    public function testAnalysisHubObjectClosure()
    {
        // Create the model.
        $model = new Model(Krexx::$pool);
        // We are not really using the globals.
        $parameter = function () {
            // Do nothing.
        };
        $model->setData($parameter);

        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method(static::IS_IN_HIVE)
            ->will($this->returnValue(false));
        $recursionMock->expects($this->once())
            ->method(static::ADD_TO_HIVE)
            ->with($parameter);
        Krexx::$pool->recursionHandler = $recursionMock;

        $this->assertEmergencyHandler(false, false);
        $this->assertEquals(static::ROUTING_MOCK_RETURN_VALUE, $this->mockRouting('processClosure', $model));
    }
}
