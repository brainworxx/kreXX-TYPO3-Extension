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
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\Krexx;

class AbstractModelTest extends AbstractTest
{
    /**
     * Test if we get the pool as well as the connector service.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::__construct
     */
    public function testConstruct()
    {
        $model = new Model(Krexx::$pool);
        $this->assertAttributeEquals(Krexx::$pool, 'pool', $model);

        $this->assertAttributeInstanceOf(Connectors::class, 'connectorService', $model);
    }

    /**
     * Test if the callback gets set.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::injectCallback
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
            ->method('setParams')
            ->will($this->returnValue(null));

        $model = new Model(Krexx::$pool);
        $this->assertEquals($model, $model->injectCallback($mockCallback));

        $this->assertAttributeEquals($mockCallback, 'callback', $model);
    }

    /**
     * The rendering will call the callback. We will mock the callback and test
     * if we get the actual output from it.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::renderMe
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
            ->method('setParams')
            ->will($this->returnValue($mockCallback));

        $model = new Model(Krexx::$pool);

        // Test id the HTML result gates returned and both methods gets called once.
        $this->assertEquals($htmlResult, $model->injectCallback($mockCallback)->renderMe());
    }

    /**
     * Test if we can add several parameters.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::addParameter
     */
    public function testAddParameter()
    {
        $parameterOne = new \stdClass();
        $parameterTwo = "some value";

        $model = new Model(Krexx::$pool);

        $model->addParameter('parameterOne', $parameterOne);
        $model->addParameter('parameterTwo', $parameterTwo);

        $expectedResult = [
            'parameterOne' => $parameterOne,
            'parameterTwo' => $parameterTwo,
        ];

        $this->assertAttributeEquals($expectedResult, 'parameters', $model);
    }

    /**
     * Test the setting of the help id. The help id sets additional text to the
     * specific analysis, to explain the output.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::setHelpid
     */
    public function testSetHelpId()
    {
        $model = new Model(Krexx::$pool);

        // Mock the message class, which will provide the help text.
        $helpText = 'some help text';
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('getHelp')
            ->will($this->returnValue($helpText));
        Krexx::$pool->messages = $messageMock;

        // Test the return value for chaining
        $this->assertEquals($model, $model->setHelpid('some id'));

        // Test if the $helpText got set inside the json.
        $this->assertAttributeEquals(['Help' => $helpText], 'json', $model);
    }

    /**
     * Test if we can add stuff to the json. Linebreaks should be removed.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::addToJson
     */
    public function testAddToJson()
    {
        $model = new Model(Krexx::$pool);
        $text = "Look\n at\r me\n\r, I'm\n\r a string";
        $key = 'some key';
        $expected = [
            $key => "Look at me, I'm a string"
        ];

        // Set the value.
        $this->assertEquals($model, $model->addToJson($key, $text));
        $this->assertAttributeEquals($expected, 'json', $model);

        //Remove the value. Should be empty now.
        $this->assertEquals($model, $model->addToJson($key, ''));
        $this->assertAttributeEquals([], 'json', $model);
    }

    /**
     * Test the getter for the json value.
     *
     * @covers \Brainworxx\Krexx\Analyse\AbstractModel::getJson
     */
    public function testGetJson()
    {
        $model = new Model(Krexx::$pool);
        $jsonData = [
            'some' => 'value',
            'to' => 'check',
        ];

        // Set it via reflections.
        $this->setValueByReflection('json', $jsonData, $model);
        $this->assertEquals($jsonData, $model->getJson());
    }
}
