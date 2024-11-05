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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;
use Brainworxx\Krexx\Tests\Fixtures\AttributesFixture;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Fixtures\ReadOnlyFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;
use ReflectionProperty;

class ThroughPropertiesTest extends AbstractHelper
{
    public const PUBLIC_STRING_PROPERTY = 'publicStringProperty';
    public const PUBLIC_INT_PROPERTY = 'publicIntProperty';
    public const PUBLIC_FLOAT_PROPERTY = 'publicFloatProperty';
    public const UNSET_PROPERTY = 'unsetProperty';
    public const PROTECTED_PROPERTY = 'protectedProperty';
    public const MY_PROPERTY = 'myProperty';
    public const LONG_STRING = 'longString';
    public const PUBLIC_STATIC = 'publicStatic';
    public const INHERITED_PUBLIC = 'inheritedPublic';
    public const INHERITED_NULL = 'inheritedNull';
    public const TRAIT_PROPERTY = 'traitProperty';
    public const JSON_COMMENT_KEY = 'Comment';
    public const JSON_DECLARED_KEY = 'Declared in';
    public const JSON_DEFAULT_VALUE = 'Default value';
    public const ATTRIBUTES_KEY = 'Attributes';
    public const PUBLIC_ARRAY_DEFAULT = 'array';
    public const READ_ONLY_STRING = 'readOnyString';
    public const PROPERTY = 'property';

    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties
     */
    protected $throughProperties;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
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
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::prepareModel
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
     * Normal test run for the property analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::prepareModel
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveConnector
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrievePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaration
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaringClassFromTraits
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::formatDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveValueStatus
     *
     * @throws \ReflectionException
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
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_STRING_PROPERTY),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_INT_PROPERTY),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::UNSET_PROPERTY),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PROTECTED_PROPERTY),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::MY_PROPERTY),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::LONG_STRING),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_STATIC),
                new ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::MY_PROPERTY),
                new ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::INHERITED_PUBLIC),
                new ReflectionProperty(ComplexPropertiesInheritanceFixture::class, static::INHERITED_NULL),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::TRAIT_PROPERTY),
                new UndeclaredProperty(new ReflectionClass($subject), $undeclaredProp),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_ARRAY_DEFAULT),
                new ReflectionProperty(ComplexPropertiesFixture::class, static::PUBLIC_FLOAT_PROPERTY)
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
        $public = 'Public ';
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
                static::JSON_DECLARED_KEY => $complexDeclarationString,
                static::JSON_DEFAULT_VALUE => '&#039;public property value&#039;'
            ],
            '->',
            '',
            $public
        );

        // publicIntProperty
        $this->assertModelValues(
            $models[1],
            123,
            'publicIntProperty',
            [
                static::JSON_COMMENT_KEY => 'Public integer property.<br /><br />&#64;var int',
                static::JSON_DECLARED_KEY => $complexDeclarationString,
                static::JSON_DEFAULT_VALUE => '123'
            ],
            '->',
            '',
            $public
        );

        // unsetProperty
        $this->assertModelValues(
            $models[2],
            null,
            static::UNSET_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Unset property is unsettling.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString,
                static::JSON_DEFAULT_VALUE => '&#039;unset me&#039;'
            ],
            '->',
            '',
            'Public Unset '
        );

        // protectedProperty
        $this->assertModelValues(
            $models[3],
            'pro tected',
            static::PROTECTED_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Protected property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString,
                static::JSON_DEFAULT_VALUE => '&#039;pro tected&#039;'
            ],
            '->',
            '',
            'Protected '
        );

        // myProperty
        $this->assertModelValues(
            $models[4],
            'asdf',
            static::MY_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'Re-Declaration of a &#039;inherited&#039; private property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString
            ],
            '->',
            '',
            'Private '
        );

        // longString
        $longString = 'gdgdfgonidoidsfogidfo idfsoigdofgoiudsfgoü dsfo goühisdfg ohisdfghioü sdoiühfg hoisdghoi sdfghiosdg sdfg dsg sdgsdf gdsg dsg';
        $this->assertModelValues(
            $models[5],
            $longString,
            static::LONG_STRING,
            [
                static::JSON_COMMENT_KEY => 'A rather long string.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationString,
                static::JSON_DEFAULT_VALUE => '&#039;' . htmlentities($longString) . '&#039;'
            ],
            '->',
            '',
            $public
        );

        // publicStatic
        $expectedJson = [static::JSON_DECLARED_KEY => $complexDeclarationString];
        if (version_compare(phpversion(), '7.4.99', '>')) {
            // We can not retrieve the default values of static properties
            // in PHP 7.x. and very early PHP 8.0 versions. We ignore the early
            // 8.0 versions for the sake of our sanity.
            $expectedJson[static::JSON_DEFAULT_VALUE] = '1';
        }

        $this->assertModelValues(
            $models[6],
            1,
            '$publicStatic',
            $expectedJson,
            '::',
            '',
            'Public Static '
        );

        // myProperty
        $this->assertModelValues(
            $models[7],
            'my property',
            static::MY_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'My private Property<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => $complexDeclarationStringInheritance,
                static::JSON_DEFAULT_VALUE => '&#039;my property&#039;'
            ],
            '->',
            '',
            'Private Inherited '
        );

        // inheritedPublic
        $this->assertModelValues(
            $models[8],
            'inherited public',
            static::INHERITED_PUBLIC,
            [
                static::JSON_DECLARED_KEY => $complexDeclarationStringInheritance,
                static::JSON_DEFAULT_VALUE => '&#039;inherited public&#039;'
            ],
            '->',
            '',
            'Public Inherited '
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
            'Protected Inherited '
        );

        // traitProperty
        $this->assertModelValues(
            $models[10],
            'trait property',
            static::TRAIT_PROPERTY,
            [
                static::JSON_COMMENT_KEY => 'A Property of a trait.<br /><br />&#64;var string',
                static::JSON_DEFAULT_VALUE => '&#039;trait property&#039;'
            ],
            '->',
            '',
            'Protected '
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
            'Public Dynamic '
        );

        // A array default value.
        $this->assertModelValues(
            $models[12],
            ['qwer', 'asdf'],
            static::PUBLIC_ARRAY_DEFAULT,
            [
                static::JSON_DECLARED_KEY => 'Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture',
                static::JSON_COMMENT_KEY => 'A simple variable with a default array.<br /><br />&#64;var string[]',
                static::JSON_DEFAULT_VALUE => 'array (<br />&nbsp;&nbsp;0 =&gt; &#039;qwer&#039;,<br />&nbsp;&nbsp;1 =&gt; &#039;asdf&#039;,<br />)'
            ],
            '->',
            '',
            'Public '
        );

        // A float default vaule.
        $this->assertModelValues(
            $models[13],
            123.456,
            static::PUBLIC_FLOAT_PROPERTY,
            [
                static::JSON_DECLARED_KEY => 'Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture',
                static::JSON_COMMENT_KEY => 'Public float property<br /><br />&#64;var float',
                static::JSON_DEFAULT_VALUE => '123.456'
            ],
            '->',
            '',
            'Public '
        );
    }

    /**
     * Provoke an error when getting the default value.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::prepareModel
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveConnector
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrievePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaration
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaringClassFromTraits
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::formatDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveValueStatus
     */
    public function testCallMeError()
    {
        if (version_compare(phpversion(), '7.4.99', '<')) {
            $this->markTestSkipped('Wrong PHP Version');
        }

        // Create a fixture.
        $refPropertyMock = $this->createMock(ReflectionProperty::class);
        $refPropertyMock->expects($this->any())
            ->method('getDefaultValue')
            ->willThrowException(new \Exception());
        $refPropertyMock->expects($this->any())
            ->method('getName')
            ->willReturn('someValue');
        $refPropertyMock->expects($this->any())
            ->method('isStatic')
            ->willReturn(false);
        $refPropertyMock->expects($this->any())
            ->method('isProtected')
            ->willReturn(false);
        $refPropertyMock->expects($this->any())
            ->method('isPrivate')
            ->willReturn(false);
        $refPropertyMock->expects($this->any())
            ->method('getDeclaringClass')
            ->willReturn(new \ReflectionClass(PublicFixture::class));
        $refPropertyMock->expects($this->any())
            ->method('getDocComment')
            ->willReturn('');


        $subject = new ComplexPropertiesFixture();
        $fixture = [
            $this->throughProperties::PARAM_REF => new ReflectionClass($subject),
            $this->throughProperties::PARAM_DATA => [$refPropertyMock]
        ];

        // Inject the nothing-router.
        $routeNothing = new RoutingNothing(Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;
        $this->mockEmergencyHandler();

        // Run the test
        $this->throughProperties
            ->setParameters($fixture)
            ->callMe();

        $model = $routeNothing->model[0];

        $this->assertSame('&#039;whatever&#039;', $model->getJson()['Default value']);
    }

    /**
     * Normal test run for the property analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::prepareModel
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveConnector
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrievePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaration
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaringClassFromTraits
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::formatDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveValueStatus
     */
    public function testCallMeAttributes()
    {
        // Test the events.
        $this->mockEventService(
            [$this->startEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties]
        );

        // Create a fixture.
        $subject = new AttributesFixture();
        $fixture = [
            $this->throughProperties::PARAM_REF => new ReflectionClass($subject),
            $this->throughProperties::PARAM_DATA => [
                new ReflectionProperty(AttributesFixture::class, static::PROPERTY),
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

        // Looking at the attributes.
        if (method_exists(ReflectionClass::class, 'getAttributes')) {
            $json = [
                static::JSON_DECLARED_KEY => AttributesFixture::class,
                static::ATTRIBUTES_KEY => 'Brainworxx\Krexx\Tests\Fixtures\Property()'
            ];
        } else {
            $json = [
                static::JSON_DECLARED_KEY => AttributesFixture::class,
            ];
        }
        $this->assertModelValues(
            $models[0],
            null,
            static::PROPERTY,
            $json,
            '->',
            '',
            'Public '
        );
    }

    /**
     * Special tests for PHP 8, actually with some 7.4'er stuff.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::prepareModel
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveConnector
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrievePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaration
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveDeclaringClassFromTraits
     * @covers \Brainworxx\Krexx\Analyse\Declaration\PropertyDeclaration::retrieveNamedPropertyType
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::formatDefaultValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::retrieveValueStatus
     */
    public function testCallMePhpEight()
    {
        if (version_compare(phpversion(), '8.0.99', '<')) {
            $this->markTestSkipped('Wrong PHP Version');
        }

        // Test the events.
        $this->mockEventService(
            [$this->startEvent, $this->throughProperties],
            [$this->endEvent, $this->throughProperties]
        );

        $subject = new ReadOnlyFixture();
        $fixture = [
            $this->throughProperties::PARAM_REF => new ReflectionClass($subject),
            $this->throughProperties::PARAM_DATA => [
                new ReflectionProperty(ReadOnlyFixture::class, static::READ_ONLY_STRING),
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
        $models = $routeNothing->model;

        $this->assertModelValues(
            $models[0],
            null,
            static::READ_ONLY_STRING,
            [
                static::JSON_COMMENT_KEY => 'An uninitialized, readonly variable.<br /><br />&#64;var string',
                static::JSON_DECLARED_KEY => 'ReadOnlyFixture.php<br />in class: Brainworxx\Krexx\Tests\Fixtures\ReadOnlyFixture',
                'Typed as' => 'string'
            ],
            '->',
            '',
            'Public Readonly Uninitialized '
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
     * @param string $additional
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
        $this->assertEquals($additional, $model->getAdditional(), $model->getName());
    }

    /**
     * Testing the property name analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::isPropertyNameNormal
     */
    public function testIsPropertyNameNormal()
    {
        $this->assertTrue($this->throughProperties->isPropertyNameNormal('getValue'));
        $this->assertFalse($this->throughProperties->isPropertyNameNormal('get value'));
        $this->assertTrue($this->throughProperties->isPropertyNameNormal('getValue'));
        $this->assertFalse($this->throughProperties->isPropertyNameNormal("\xEF\xBB\xBF"));
        $this->assertFalse($this->throughProperties->isPropertyNameNormal('x' . "\xEF\xBB\xBF" . 'y'));
    }
}
