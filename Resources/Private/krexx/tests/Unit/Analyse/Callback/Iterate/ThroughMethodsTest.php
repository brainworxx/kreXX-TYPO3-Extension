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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Tests\Fixtures\AttributeFixture;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;
use ReflectionMethod;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ThroughMethods::class, '__construct')]
#[CoversMethod(ThroughMethods::class, 'callMe')]
#[CoversMethod(ThroughMethods::class, 'retrieveConnectorType')]
#[CoversMethod(ThroughMethods::class, 'retrieveParameters')]
#[CoversMethod(MethodDeclaration::class, 'retrieveDeclaration')]
#[CoversMethod(MethodDeclaration::class, 'retrieveDeclaringReflection')]
#[CoversMethod(ThroughMethods::class, 'getDeclarationKeywords')]
#[CoversMethod(ThroughMethods::class, 'retrieveMethodData')]
class ThroughMethodsTest extends AbstractHelper
{
    /**
     * Our testing specimen
     *
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods
     */
    protected $throughMethods;

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods::callMe::end';


    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->throughMethods = new ThroughMethods(Krexx::$pool);
    }

    /**
     * Testing the creation of the comment analysis.
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(
            Methods::class,
            $this->retrieveValueByReflection('commentAnalysis', $this->throughMethods)
        );
    }

    /**
     * Testing an analysis without any methods to look at.
     */
    public function testCallMeEmpty()
    {
        // Test for the start event
        $this->mockEventService(
            [$this->startEvent, $this->throughMethods]
        );

        // Create an empty fixture
        $fixture = [
            $this->throughMethods::PARAM_REF => new ReflectionClass(ComplexMethodFixture::class),
            $this->throughMethods::PARAM_DATA => []
        ];

        // Run the test
        $this->throughMethods
            ->setParameters($fixture)
            ->callMe();
    }

    /**
     * Normal testrun for the method analysis.
     */
    public function testCallMeNormal()
    {
        // Test the event calling.
        $this->mockEventService(
            [$this->startEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods]
        );

        // Create the empty fixture
        $fixture = [
            $this->throughMethods::PARAM_REF => new ReflectionClass(ComplexMethodFixture::class),
            $this->throughMethods::PARAM_DATA => [
                new ReflectionMethod(ComplexMethodFixture::class, 'publicMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'protectedMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'privateMethod'),
                new ReflectionMethod(MethodsFixture::class, 'privateMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'troublesomeMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'finalMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'parameterizedMethod'),
                new ReflectionMethod(ComplexMethodFixture::class, 'traitFunction')
            ]
        ];

        // Inject the render nothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;
        // Overwrite the callback.
        Krexx::$pool->rewrite[ThroughMeta::class] = CallbackNothing::class;

        // Run the test.
        $this->throughMethods
            ->setParameters($fixture)
            ->callMe();

        // Check the result
        $models = $renderNothing->model['renderExpandableChild'];

        $methodFixtureClass = 'Brainworxx\Krexx\Tests\Fixtures\MethodsFixture';
        $methodFixtureFile = 'MethodsFixture.php';
        $complexMethodFixtureClass = 'Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture';
        $complexMethodFixtureFile = 'ComplexMethodFixture.php';

        // publicMethod
        $this->assertModelValues(
            $models[0],
            $fixture[$this->throughMethods::PARAM_DATA][0]->name,
            'Public Inherited method',
            '->',
            '()',
            '',
            'Some comment.',
            $methodFixtureFile,
            $methodFixtureClass,
        );

        // protectedMethod
        $this->assertModelValues(
            $models[1],
            $fixture[$this->throughMethods::PARAM_DATA][1]->name,
            'Protected Inherited method',
            '->',
            '()',
            '',
            'More comments',
            $methodFixtureFile,
            $methodFixtureClass,
        );

        // privateMethod
        // Not to be confused with the inheriteted private method.
        $this->assertModelValues(
            $models[2],
            $fixture[$this->throughMethods::PARAM_DATA][2]->name,
            'Private method',
            '->',
            '()',
            '',
            'Private function',
            $complexMethodFixtureFile,
            $complexMethodFixtureClass,
        );

        // privateMethod
        // The inherited one.
        $this->assertModelValues(
            $models[3],
            $fixture[$this->throughMethods::PARAM_DATA][3]->name,
            'Private Inherited method',
            '->',
            '()',
            '',
            'Private method. Duh.',
            $methodFixtureFile,
            $methodFixtureClass,
        );

        // troublesomeMethod
        $this->assertModelValues(
            $models[4],
            $fixture[$this->throughMethods::PARAM_DATA][4]->name,
            'Public Inherited method',
            '->',
            '(\someNotExistingClass $parameter)',
            '\someNotExistingClass $parameter',
            'Asking politely for trouble here',
            $methodFixtureFile,
            $methodFixtureClass,
        );

        // finalMethod
        $this->assertModelValues(
            $models[5],
            $fixture[$this->throughMethods::PARAM_DATA][5]->name,
            'Public Final method',
            '->',
            '()',
            '',
            'Final function',
            $complexMethodFixtureFile,
            $complexMethodFixtureClass,
        );

        // parameterizedMethod
        $this->assertModelValues(
            $models[6],
            $fixture[$this->throughMethods::PARAM_DATA][6]->name,
            'Public method',
            '->',
            '($parameter)',
            '$parameter',
            '&#64;param $parameter',
            $complexMethodFixtureFile,
            $complexMethodFixtureClass,
        );

        // traitFunction
        $this->assertModelValues(
            $models[7],
            $fixture[$this->throughMethods::PARAM_DATA][7]->name,
            'Protected method',
            '->',
            '()',
            '',
            'Do something.',
            'TraitFixture.php',
            'Brainworxx\\Krexx\\Tests\\Fixtures\\TraitFixture',
        );
    }

    /**
     * Testing the method analysis with attributes.
     */
    public function testCallMeAttributes()
    {
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        // Test the event calling.
        $this->mockEventService(
            [$this->startEvent, $this->throughMethods],
            [$this->endEvent, $this->throughMethods]
        );

        $fixture = [
            $this->throughMethods::PARAM_REF => new ReflectionClass(AttributeFixture::class),
            $this->throughMethods::PARAM_DATA => [
                new ReflectionMethod(AttributeFixture::class, 'getFoo'),
            ]
        ];

        // Inject the render nothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;
        // Overwrite the callback.
        Krexx::$pool->rewrite[ThroughMeta::class] = CallbackNothing::class;

        // Run the test.
        $this->throughMethods
            ->setParameters($fixture)
            ->callMe();

        // Check the result
        /** @var Model $model */
        $model = $renderNothing->model['renderExpandableChild'][0];
        $this->assertStringContainsString(
            "#[Brainworxx\Krexx\Tests\Fixtures\Phobject\Attributes\Stuff(",
            $model->getParameters()[$this->throughMethods::PARAM_DATA]['Attributes']
        );
    }

    /**
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @param string $name
     * @param string $type
     * @param string $connectorLeft
     * @param string $connectorRight
     * @param string $connectorParameter
     * @param string $comment
     * @param string $declaredInFile
     * @param string $declaredInClass
     */
    protected function assertModelValues(
        Model $model,
        string $name,
        string $type,
        string $connectorLeft,
        string $connectorRight,
        string $connectorParameter,
        string $comment,
        string $declaredInFile,
        string $declaredInClass
    ) {
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($type, $model->getType());
        $this->assertEquals($connectorLeft, $model->getConnectorLeft());
        $this->assertEquals($connectorRight, $model->getConnectorRight());
        $this->assertEquals($connectorParameter, $model->getConnectorParameters());
        $this->assertStringContainsString(
            $comment,
            $model->getParameters()[$this->throughMethods::PARAM_DATA]['Comment']
        );

        $this->assertStringContainsString(
            $declaredInFile,
            $model->getParameters()[$this->throughMethods::PARAM_DATA]['Declared in']
        );
        $this->assertStringContainsString(
            $declaredInClass,
            $model->getParameters()[$this->throughMethods::PARAM_DATA]['Declared in']
        );
        $this->assertTrue(
            $this->throughMethods->getParameters()[$this->throughMethods::PARAM_REFLECTION_METHOD] instanceof ReflectionMethod
        );
    }
}
