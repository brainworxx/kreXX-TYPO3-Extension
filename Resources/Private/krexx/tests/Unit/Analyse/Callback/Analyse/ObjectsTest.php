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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Constants;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\DebugMethods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PrivateProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ProtectedProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ErrorObject;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Fixtures\TraversableFixture;
use Brainworxx\Krexx\Krexx;

class ObjectsTest extends AbstractTest
{
    /**
     * The class instance we are using for the tests.
     *
     * @var array
     */
    protected $fixture = [];

    /**
     * @var Objects
     */
    protected $objects;

    protected function setUp()
    {
        $this->fixture['data'] = new SimpleFixture();
        $this->fixture['name'] = 'some string';

        parent::setUp();

        // Prevent the class from going deeper!
        Krexx::$pool->rewrite = [
            PublicProperties::class => CallbackNothing::class,
            Getter::class => CallbackNothing::class,
            ProtectedProperties::class => CallbackNothing::class,
            PrivateProperties::class => CallbackNothing::class,
            Constants::class => CallbackNothing::class,
            Methods::class => CallbackNothing::class,
            Traversable::class => CallbackNothing::class,
            DebugMethods::class => CallbackNothing::class,
            ErrorObject::class => CallbackNothing::class,
            Meta::class => CallbackNothing::class,
        ];

        $this->objects = new Objects(Krexx::$pool);
    }

    /**
     * All callbacks here get the same parameters.
     *
     * @param $parameters
     */
    protected function parametersTest(array $parameters)
    {
        $this->assertEquals(3, count($parameters));
        $this->assertTrue(isset($parameters['data']));
        $this->assertTrue(isset($parameters['ref']));
        $this->assertTrue(isset($parameters['name']));
        $this->assertEquals($this->fixture['data'], $parameters['data']);
        $this->assertEquals($this->fixture['name'], $parameters['name']);
        $this->assertTrue(is_a($parameters['ref'], ReflectionClass::class));
    }

    /**
     * Testing the start event and if other analysis classes are getting used,
     * according to the configuration.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeEvent()
    {
        // Test if start event has fired
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects::callMe::start', $this->objects]
        );

        $this->objects->setParameters($this->fixture)
            ->callMe();
    }

    /**
     * Test, if the public properties are getting analysed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMePublic()
    {
        // Test analyse public
        Krexx::$pool->rewrite[PublicProperties::class] = CallbackCounter::class;

        $this->objects->setParameters($this->fixture)
            ->callMe();

        // Was it called?
        $this->assertEquals(1, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the getter get analysed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeGetter()
    {
        // Test analyse getter true
        Krexx::$pool->rewrite[Getter::class] = CallbackCounter::class;

        // This one is depending on a setting.
        $this->setConfigValue(Fallback::SETTING_ANALYSE_GETTER, true);

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);

        // Test analyse getter false
        CallbackCounter::$counter = 0;
        $this->setConfigValue(Fallback::SETTING_ANALYSE_GETTER, false);
        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(0, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the meta stuff is analysed.
     */
    public function testCallMeMeta()
    {
        Krexx::$pool->rewrite[Meta::class] = CallbackCounter::class;
        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);
    }

    /**
     * Test, if the protected properties are analysed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeProtected()
    {
        // Test analyse protected true
        Krexx::$pool->rewrite[ProtectedProperties::class] = CallbackCounter::class;
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED, true);

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);

        // Test analyse protected false
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED, false);
        CallbackCounter::$counter = 0;

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(0, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the private properties are analysed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMePrivate()
    {
        // Test analyse private true
        Krexx::$pool->rewrite[PrivateProperties::class] = CallbackCounter::class;
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE, true);

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);

        // Test analyse private false
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE, false);
        CallbackCounter::$counter = 0;

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(0, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the scope analysis is used.
     *
     * Pun not intended.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeConstants()
    {
        Krexx::$pool->rewrite[Constants::class] = CallbackCounter::class;

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the method analysis is used.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeMethods()
    {
        Krexx::$pool->rewrite[Methods::class] = CallbackCounter::class;

        $this->objects->setParameters($this->fixture)
            ->callMe();

        $this->assertEquals(1, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test with traversable deactivated and with a traversable class
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeTraversableDeactivatedTraversable()
    {
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, false);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->fixture['data'] = new TraversableFixture();
        $this->objects->setParameters($this->fixture)->callMe();
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test with traversable deactivated and with a normal class
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeTraversableDeactivatedNormal()
    {
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, false);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->objects->setParameters($this->fixture)->callMe();
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test with traversable activated and with a normal class
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeTraversableActivatedTraversable()
    {
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, true);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->objects->setParameters($this->fixture)->callMe();
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test, if the traversable part is called.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeTraversableActivated()
    {
        // Test with traversable activated and with a traversable class
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, true);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->fixture['data'] = new TraversableFixture();
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the debug methods analysis is triggered.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects::dumpStuff
     */
    public function testCallMeDebugMethods()
    {
        Krexx::$pool->rewrite[DebugMethods::class] = CallbackCounter::class;
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }
}
