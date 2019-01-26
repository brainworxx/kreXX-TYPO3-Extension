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
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class ThroughPropertiesTest extends AbstractTest
{
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
        $this->throughProperties = new ThroughProperties(\Krexx::$pool);
    }

    /**
     * Testing an analysis without any methods to look at.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     */
    public function testCallMeEmpty()
    {
        // Test for the start event
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::start', $this->throughProperties]
        );

        // Create an empty fixture
        $fixture = [
            $this->throughProperties::PARAM_REF => new \ReflectionClass(PublicFixture::class),
            $this->throughProperties::PARAM_DATA => []
        ];

        // Run the test
        $this->throughProperties
            ->setParams($fixture)
            ->callMe();
    }

    /**
     * Normal testrun for the property analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties::getAdditionalData
     */
    public function testCallMeNormal()
    {
        // Test the events.
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::start', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties::callMe::end', $this->throughProperties]
        );

        // Create a fixture.
        $subject = new ComplexPropertiesFixture();
        $fixture = [
            $this->throughProperties::PARAM_REF => new ReflectionClass($subject),
            $this->throughProperties::PARAM_DATA => [
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'publicStringProperty'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'publicIntProperty'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'unsetProperty'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'protectedProperty'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'myProperty'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'longString'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'publicStatic'),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, 'myProperty'),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, 'inheritedPublic'),
                new \ReflectionProperty(ComplexPropertiesInheritanceFixture::class, 'inheritedNull'),
                new \ReflectionProperty(ComplexPropertiesFixture::class, 'traitProperty')
            ]
        ];

        // Inject the nothing-router.
        $routeNothing = new RoutingNothing(\Krexx::$pool);
        \Krexx::$pool->routing = $routeNothing;
        $this->mockEmergencyHandler();

        // Run the test
        $this->throughProperties
            ->setParams($fixture)
            ->callMe();

        // Retrieve the result models and assert them.
        $models = $routeNothing->model;

        $complexDeclarationString = '.../tests/Fixtures/ComplexPropertiesFixture.php<br />in class: Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture';
        $complexDeclarationStringInheritance = '.../tests/Fixtures/ComplexPropertiesInheritanceFixture.php<br />in class: Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture';

        // publicStringProperty
        $this->assertEquals('public property value', $models[0]->getData());
        $this->assertEquals('publicStringProperty', $models[0]->getName());
        $this->assertEquals(
            [
                'Comment' => 'Public string property.<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationString
            ],
            $models[0]->getJson()
        );
        $this->assertEquals('public ', $models[0]->getAdditional());
        $this->assertEquals('->', $models[0]->getConnectorLeft());
        $this->assertEquals('', $models[0]->getConnectorRight());

        // publicIntProperty
        $this->assertEquals(123, $models[1]->getData());
        $this->assertEquals('publicIntProperty', $models[1]->getName());
        $this->assertEquals(
            [
                'Comment' => 'Public integer property.<br /><br />&#64;var int',
                'Declared in' => $complexDeclarationString
            ],
            $models[1]->getJson()
        );
        $this->assertEquals('public ', $models[1]->getAdditional());
        $this->assertEquals('->', $models[1]->getConnectorLeft());
        $this->assertEquals('', $models[1]->getConnectorRight());

        // unsetProperty
        $this->assertEquals(null, $models[2]->getData());
        $this->assertEquals('unsetProperty', $models[2]->getName());
        $this->assertEquals(
            [
                'Comment' => 'Unset property is unsettling.<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationString
            ],
            $models[2]->getJson()
        );
        $this->assertEquals('public unset ', $models[2]->getAdditional());
        $this->assertEquals('->', $models[2]->getConnectorLeft());
        $this->assertEquals('', $models[2]->getConnectorRight());

        // protectedProperty
        $this->assertEquals('pro tected', $models[3]->getData());
        $this->assertEquals('protectedProperty', $models[3]->getName());
        $this->assertEquals(
            [
                'Comment' => 'Protected property<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationString
            ],
            $models[3]->getJson()
        );
        $this->assertEquals('protected ', $models[3]->getAdditional());
        $this->assertEquals('->', $models[3]->getConnectorLeft());
        $this->assertEquals('', $models[3]->getConnectorRight());

        // myProperty
        $this->assertEquals('asdf', $models[4]->getData());
        $this->assertEquals('myProperty', $models[4]->getName());
        $this->assertEquals(
            [
                'Comment' => 'Re-Declaration of a \'inherited\' private property<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationString
            ],
            $models[4]->getJson()
        );
        $this->assertEquals('private ', $models[4]->getAdditional());
        $this->assertEquals('->', $models[4]->getConnectorLeft());
        $this->assertEquals('', $models[4]->getConnectorRight());

        // longString
        $this->assertEquals('gdgdfgonidoidsfogidfo idfsoigdofgoiudsfgoü dsfo goühisdfg ohisdfghioü sdoiühfg hoisdghoi sdfghiosdg sdfg dsg sdgsdf gdsg dsg', $models[5]->getData());
        $this->assertEquals('longString', $models[5]->getName());
        $this->assertEquals(
            [
                'Comment' => 'A rather long string.<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationString
            ],
            $models[5]->getJson()
        );
        $this->assertEquals('public ', $models[5]->getAdditional());
        $this->assertEquals('->', $models[5]->getConnectorLeft());
        $this->assertEquals('', $models[5]->getConnectorRight());

        // publicStatic
        $this->assertEquals(1, $models[6]->getData());
        $this->assertEquals('$publicStatic', $models[6]->getName());
        $this->assertEquals(['Declared in' => $complexDeclarationString], $models[6]->getJson());
        $this->assertEquals('public static ', $models[6]->getAdditional());
        $this->assertEquals('::', $models[6]->getConnectorLeft());
        $this->assertEquals('', $models[6]->getConnectorRight());

        // myProperty
        $this->assertEquals('my property', $models[7]->getData());
        $this->assertEquals('myProperty', $models[7]->getName());
        $this->assertEquals(
            [
                'Comment' => 'My private Property<br /><br />&#64;var string',
                'Declared in' => $complexDeclarationStringInheritance
            ],
            $models[7]->getJson()
        );
        $this->assertEquals('private inherited ', $models[7]->getAdditional());
        $this->assertEquals('->', $models[7]->getConnectorLeft());
        $this->assertEquals('', $models[7]->getConnectorRight());

        // inheritedPublic
        $this->assertEquals('inherited public', $models[8]->getData());
        $this->assertEquals('inheritedPublic', $models[8]->getName());
        $this->assertEquals(
            [
                'Declared in' => $complexDeclarationStringInheritance
            ],
            $models[8]->getJson()
        );
        $this->assertEquals('public inherited ', $models[8]->getAdditional());
        $this->assertEquals('->', $models[8]->getConnectorLeft());
        $this->assertEquals('', $models[8]->getConnectorRight());

        // inheritedNull
        $this->assertEquals(null, $models[9]->getData());
        $this->assertEquals('inheritedNull', $models[9]->getName());
        $this->assertEquals(
            [
                'Comment' => '&#64;var null',
                'Declared in' => $complexDeclarationStringInheritance
            ],
            $models[9]->getJson()
        );
        $this->assertEquals('->', $models[9]->getConnectorLeft());
        $this->assertEquals('', $models[9]->getConnectorRight());

        // traitProperty
        $this->assertEquals('trait property', $models[10]->getData());
        $this->assertEquals('traitProperty', $models[10]->getName());
        $this->assertEquals(
            ['Comment' => 'A Property of a trait.<br /><br />&#64;var string'],
            $models[10]->getJson()
        );
        $this->assertEquals('->', $models[10]->getConnectorLeft());
        $this->assertEquals('', $models[10]->getConnectorRight());
    }
}
