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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughProperties;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use ReflectionProperty;
use DateTime;

class PublicPropertyTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties
     */
    protected $publicProperties;

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties::callMe::start';

    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\PublicProperties::analysisEnd';

    /**
     * Create the class to test and inject the callback counter.
     *
     * {@inheritdoc}
     */
    protected function krexxUp()
    {
        parent::krexxUp();

        // Create in instance of the class to test
        $this->publicProperties = new PublicProperties(Krexx::$pool);

        // Inject the callback counter
        Krexx::$pool->rewrite = [
            ThroughProperties::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Test the public property analysis, without any public ones in the
     * fixture
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::getReflectionPropertiesData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::handleUndeclaredProperties
     *
     * @throws \ReflectionException
     */
    public function testCallMeNoPublic()
    {
        // Test start event
        $this->mockEventService([$this->startEvent, $this->publicProperties]);

        // Fixture without any private properties.
        $data = new MethodsFixture();
        $fixture = [
            'data' => $data,
            'name' => 'some name',
            'ref' => new ReflectionClass($data)
        ];

        // Run the test.
        $this->publicProperties
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test the public property analysis, with public ones in the fixture.
     * We also add some undeclared ones to the mix.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::getReflectionPropertiesData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::handleUndeclaredProperties
     *
     * @throws \ReflectionException
     */
    public function testCallMeWithPublic()
    {
        // Set up the events
        $this->mockEventService([$this->startEvent, $this->publicProperties], [$this->endEvent, $this->publicProperties]);

        // Create a fixture with several private properties with inheritance.
        $data = new PublicFixture();
        unset($data->value1);
        $data->undeclared = 'undeclared Property';
        $fixture = [
            'data' => $data,
            'name' => 'some name',
            'ref' => new ReflectionClass($data)
        ];

         // Run the test.
        $this->publicProperties
            ->setParameters($fixture)
            ->callMe();

        // Check if called
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check if parameters are set.
        $params = CallbackCounter::$staticParameters[0];
        $this->assertEquals($fixture['ref'], $params['ref']);

        // Create the expectations.
        $expectations = [
            new ReflectionProperty(PublicFixture::class, 'someValue'),
            new ReflectionProperty(PublicFixture::class, 'static'),
            new UndeclaredProperty($fixture['ref'], 'undeclared'),
            new ReflectionProperty(PublicFixture::class, 'value1'),
            new ReflectionProperty(SimpleFixture::class, 'value2'),
        ];

        $this->assertEquals($expectations, $params['data']);
    }

    /**
     * Testing the "public" properties of a date time analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::getReflectionPropertiesData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::handleUndeclaredProperties
     */
    public function testCallMeDateTime()
    {
        // Set up the events
        $this->mockEventService([$this->startEvent, $this->publicProperties], [$this->endEvent, $this->publicProperties]);

        // Create a fixture with a date time object.
        $data = new DateTime('now');
        $fixture = [
            'data' => $data,
            'name' => 'date time',
            'ref' => new ReflectionClass($data)
        ];

        // Run the test.
        $this->publicProperties
            ->setParameters($fixture)
            ->callMe();

        $params = CallbackCounter::$staticParameters[0];
        $expectations = [
            (new UndeclaredProperty($fixture['ref'], 'date'))->setIsPublic(false),
            (new UndeclaredProperty($fixture['ref'], 'timezone'))->setIsPublic(false),
            (new UndeclaredProperty($fixture['ref'], 'timezone_type'))->setIsPublic(false),
        ];

        $this->assertEquals($expectations, $params['data']);
    }
}
