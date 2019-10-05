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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;

class ThroughPropertiesTest extends AbstractTest
{
    const PUBLIC_STRING_PROPERTY = 'publicStringProperty';
    const PUBLIC_INT_PROPERTY = 'publicIntProperty';
    const UNSET_PROPERTY = 'unsetProperty';
    const PROTECTED_PROPERTY = 'protectedProperty';
    const MY_PROPERTY = 'myProperty';
    const LONG_STRING = 'longString';
    const PUBLIC_STATIC = 'publicStatic';
    const INHERITED_PUBLIC = 'inheritedPublic';
    const INHERITED_NULL = 'inheritedNull';
    const TRAIT_PROPERTY = 'traitProperty';
    const JSON_COMMENT_KEY = 'Comment';
    const JSON_DECLARED_KEY = 'Declared in';

    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties
     */
    protected $throughProperties;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->throughProperties = new ThroughProperties(Krexx::$pool);
    }

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end';

    /**
     * Testing an analysis without any methods to look at.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     */
    public function testCallMeEmpty()
    {
        // Test for the start event
        $this->mockEventService(
            [$this->startEvent, $this->throughProperties]
        );

        // Create an empty fixture
        $fixture = [
            $this->throughProperties::PARAM_REF => new \ReflectionClass(PublicFixture::class),
            $this->throughProperties::PARAM_DATA => []
        ];

        // Run the test
        $this->throughProperties
            ->setParameters($fixture)
            ->callMe();
    }

    /**
     * Normal testrun for the property analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveDeclarationPlace
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveFilenameFromTraits
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     */
    public function testCallMeNormal()
    {
        // Test the events.
        $this->mockEventService(
            [$this->startEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties]
        );

        // Create a fixture.
        $subject = new ComplexPropertiesFixture();
        $undeclaredProp = 'special butterfly';
        $subject->$undeclaredProp = null;
        $fixture = [
            $this->throughProperties::PARAM_REF => new ReflectionClass($subject),
            $this->throughProperties::PARAM_DATA => [
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_STRING_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_INT_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::UNSET_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::PROTECTED_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::MY_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::LONG_STRING),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_STATIC),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::MY_PROPERTY),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::INHERITED_PUBLIC),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::INHERITED_NULL),
                new \ReflectionProperty(ComplexPropertiesFixture::class, static::TRAIT_PROPERTY),
                new UndeclaredProperty(new ReflectionClass($subject), $undeclaredProp)
            ]
        ];

        // Inject the nothing-router.
        $routeNothing = new RoutingNothing(Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;
        $this->mockEmergencyHandler();

        // Run the test
        $this->throughProperties
            ->setParameters($fixture)
            ->callMe();

        // Retrieve the result models and assert them.
        $models = $routeNothing->model;

        $complexDeclarationString = DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR .
            'Fixtures' . DIRECTORY_SEPARATOR .
            'ComplexPropertiesFixture.php<br />in class: Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture';
        $complexDeclarationStringInheritance = DIRECTORY_SEPARATOR . 'tests' .
            DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR .
            'ComplexPropertiesInheritanceFixture.php<br />in class: Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture';

        // publicStringProperty
        $this->assertModelValues(
            $models[0],
            'public property value',
            static::PUBLIC_STRING_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Public string property.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'public '
        );

        // publicIntProperty
        $this->assertModelValues(
            $models[1],
            123,
            'publicIntProperty',
            [
                static::JSON_COMMENT_KEY => 'Public integer property.<br /><br />&#64;var int',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'public '
        );

        // unsetProperty
        $this->assertModelValues(
            $models[2],
            null,
            static::UNSET_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Unset property is unsettling.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'public unset '
        );

        // protectedProperty
        $this->assertModelValues(
            $models[3],
            'pro tected',
            static::PROTECTED_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Protected property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'protected '
        );

        // myProperty
        $this->assertModelValues(
            $models[4],
            'asdf',
            static::MY_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Re-Declaration of a \'inherited\' private property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'private '
        );

        // longString
        $this->assertModelValues(
            $models[5],
            'gdgdfgonidoidsfogidfo idfsoigdofgoiudsfgoü dsfo goühisdfg ohisdfghioü sdoiühfg hoisdghoi sdfghiosdg sdfg dsg sdgsdf gdsg dsg',
            static::LONG_STRING,
            [
                static::JSON_COMMENT_KEY => 'A rather long string.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'public '
        );

        // publicStatic
        $this->assertModelValues(
            $models[6],
            1,
            '$publicStatic',
            [static::JSON_DECLARED_KEY => $complexDeclarationString],
            '::',
            '',
            'public static '
        );

        // myProperty
        $this->assertModelValues(
            $models[7],
            'my property',
            static::MY_PROPERTY,
            [
                static::JSON_COMMENT_KEY =>'My private Property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationStringInheritance
            ],
            '->',
            '',
            'private inherited '
        );

        // inheritedPublic
        $this->assertModelValues(
            $models[8],
            'inherited public',
            static::INHERITED_PUBLIC,
            [
                static::JSON_DECLARED_KEY => $complexDeclarationStringInheritance
            ],
            '->',
            '',
            'public inherited '
        );

        // inheritedNull
        $this->assertModelValues(
            $models[9],
            null,
            static::INHERITED_NULL,
            [
                static::JSON_COMMENT_KEY => '&#64;var null',
                static::JSON_DECLARED_KEY => $complexDeclarationStringInheritance
            ],
            '->',
            '',
            'protected inherited '
        );

        // traitProperty
        $this->assertModelValues(
            $models[10],
            'trait property',
            static::TRAIT_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'A Property of a trait.<br /><br />&#64;var string'
            ],
            '->',
            '',
            'protected '
        );

        // The special undeclared one.
        // Please note, that a dynamic unset property is not possible.
        $this->assertModelValues(
            $models[11],
            null,
            $undeclaredProp,
            [
                static::JSON_DECLARED_KEY => 'undeclared'
            ],
            '->{\'',
            '\'}',
            'public dynamic property '
        );
    }

    /**
     * Simply assert the stuff inside the model.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @param mixed $data
     * @param string $name
     * @param array $json
     * @param string $conectorLeft
     * @param string $connectorRight
     */
    protected function assertModelValues(
        Model $model,
        $data,
        string $name,
        array $json,
        string $conectorLeft,
        string $connectorRight,
        string $additional
    ) {
        // The declared in path may differ, depending where the kreXX lib is
        // installed. We only test the ends with part.
        $testJson = $model->getJson();
        if (isset($testJson[static::JSON_DECLARED_KEY])) {
            $testDeclaredIn = $testJson[static::JSON_DECLARED_KEY];
            $declaredIn = $json[static::JSON_DECLARED_KEY];
            unset($testJson[static::JSON_DECLARED_KEY]);
            unset($json[static::JSON_DECLARED_KEY]);
            $this->assertStringEndsWith($declaredIn, $testDeclaredIn);
        }

        $this->assertEquals($json, $testJson);
        $this->assertEquals($data, $model->getData());
        $this->assertEquals($name, $model->getName());
        $this->assertEquals($conectorLeft, $model->getConnectorLeft());
        $this->assertEquals($connectorRight, $model->getConnectorRight());
        $this->assertEquals($additional, $model->getAdditional());
    }
}
