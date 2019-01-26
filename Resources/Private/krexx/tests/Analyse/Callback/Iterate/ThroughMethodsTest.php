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

namespace Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethodAnalysis;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ThroughMethodsTest extends AbstractTest
{
    /**
     * Our testing specimen
     *
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods
     */
    protected $throughMethods;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->throughMethods = new ThroughMethods(\Krexx::$pool);
    }

    /**
     * Testing an analysis without any methods to look at.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::callMe
     */
    public function testCallMeEmpty()
    {
        // Test for the start event
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::start', $this->throughMethods]
        );

        // Create an empty fixture
        $fixture = [
            $this->throughMethods::PARAM_REF => new \ReflectionClass(ComplexMethodFixture::class),
            $this->throughMethods::PARAM_DATA => []
        ];

        // Run the test
        $this->throughMethods
            ->setParams($fixture)
            ->callMe();
    }

    /**
     * Normal testrun for the method analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::getDeclarationPlace
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::getDeclarationKeywords
     */
    public function testCallMeNormal()
    {
        // Test the event calling.
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::start', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end', $this->throughMethods]
        );

        // Create the empty fixture
        $fixture = [
            $this->throughMethods::PARAM_REF => new \ReflectionClass(ComplexMethodFixture::class),
            $this->throughMethods::PARAM_DATA => [
                new \ReflectionMethod(ComplexMethodFixture::class, 'publicMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'protectedMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'privateMethod'),
                new \ReflectionMethod(MethodsFixture::class, 'privateMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'troublesomeMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'finalMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'parameterizedMethod'),
                new \ReflectionMethod(ComplexMethodFixture::class, 'traitFunction')
            ]
        ];

        // Inject the render nothing.
        $renderNothing = new RenderNothing(\Krexx::$pool);
        \Krexx::$pool->render = $renderNothing;
        // Overwrite the callback.
        \Krexx::$pool->rewrite[ThroughMethodAnalysis::class] = CallbackNothing::class;

        // Run the test.
        $this->throughMethods
            ->setParams($fixture)
            ->callMe();

        // Check the result
        $models = $renderNothing->model['renderExpandableChild'];

        // publicMethod
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][0]->name, $models[0]->getName());
        $this->assertEquals('public inherited method', $models[0]->getType());
        $this->assertEquals('->', $models[0]->getConnectorLeft());
        $this->assertEquals('()', $models[0]->getConnectorRight());
        $this->assertEquals('', $models[0]->getConnectorParameters());
        $this->assertEquals('Some comment.', $models[0]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('MethodsFixture.php', $models[0]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\MethodsFixture', $models[0]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertTrue($this->throughMethods->getParameters()[$this->throughMethods::PARAM_REF_METHOD] instanceof \ReflectionMethod);

        // protectedMethod
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][1]->name, $models[1]->getName());
        $this->assertEquals('protected inherited method', $models[1]->getType());
        $this->assertEquals('->', $models[1]->getConnectorLeft());
        $this->assertEquals('()', $models[1]->getConnectorRight());
        $this->assertEquals('', $models[1]->getConnectorParameters());
        $this->assertEquals('More comments', $models[1]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('MethodsFixture.php', $models[1]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\MethodsFixture', $models[1]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // privateMethod
        // Not to be confused with the inheriteted private method.
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][2]->name, $models[2]->getName());
        $this->assertEquals('private method', $models[2]->getType());
        $this->assertEquals('->', $models[2]->getConnectorLeft());
        $this->assertEquals('()', $models[2]->getConnectorRight());
        $this->assertEquals('', $models[2]->getConnectorParameters());
        $this->assertEquals('Private function', $models[2]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('ComplexMethodFixture.php', $models[2]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture', $models[2]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // privateMethod
        // The inherited one.
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][3]->name, $models[3]->getName());
        $this->assertEquals('private inherited method', $models[3]->getType());
        $this->assertEquals('->', $models[3]->getConnectorLeft());
        $this->assertEquals('()', $models[3]->getConnectorRight());
        $this->assertEquals('', $models[3]->getConnectorParameters());
        $this->assertEquals('Private method. Duh.', $models[3]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('MethodsFixture.php', $models[3]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\MethodsFixture', $models[3]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // troublesomeMethod
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][4]->name, $models[4]->getName());
        $this->assertEquals('public inherited method', $models[4]->getType());
        $this->assertEquals('->', $models[4]->getConnectorLeft());
        $this->assertEquals('(<small>someNotExistingClass $parameter</small>)', $models[4]->getConnectorRight());
        $this->assertEquals('someNotExistingClass $parameter', $models[4]->getConnectorParameters());
        $this->assertContains('Asking politely for trouble here', $models[4]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('MethodsFixture.php', $models[4]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\MethodsFixture', $models[4]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // finalMethod
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][5]->name, $models[5]->getName());
        $this->assertEquals('public final method', $models[5]->getType());
        $this->assertEquals('->', $models[5]->getConnectorLeft());
        $this->assertEquals('()', $models[5]->getConnectorRight());
        $this->assertEquals('', $models[5]->getConnectorParameters());
        $this->assertEquals('Final function', $models[5]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('ComplexMethodFixture.php', $models[5]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture', $models[5]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // parameterizedMethod
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][6]->name, $models[6]->getName());
        $this->assertEquals('public method', $models[6]->getType());
        $this->assertEquals('->', $models[6]->getConnectorLeft());
        $this->assertEquals('(<small>$parameter</small>)', $models[6]->getConnectorRight());
        $this->assertEquals('$parameter', $models[6]->getConnectorParameters());
        $this->assertEquals('&#64;param $parameter', $models[6]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('ComplexMethodFixture.php', $models[6]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture', $models[6]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);

        // traitFunction
        $this->assertEquals($fixture[$this->throughMethods::PARAM_DATA][7]->name, $models[7]->getName());
        $this->assertEquals('protected method', $models[7]->getType());
        $this->assertEquals('->', $models[7]->getConnectorLeft());
        $this->assertEquals('()', $models[7]->getConnectorRight());
        $this->assertEquals('', $models[7]->getConnectorParameters());
        $this->assertEquals('Do something.', $models[7]->getParameters()[$this->throughMethods::PARAM_DATA]['comments']);
        $this->assertContains('TraitFixture.php', $models[7]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
        $this->assertContains('Brainworxx\Krexx\Tests\Fixtures\TraitFixture', $models[7]->getParameters()[$this->throughMethods::PARAM_DATA]['declared in']);
    }
}