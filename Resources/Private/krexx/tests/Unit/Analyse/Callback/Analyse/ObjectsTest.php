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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
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
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\OpaqueRessource;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Logging\Model;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Fixtures\TraversableFixture;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Exception;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Objects::class, 'callMe')]
#[CoversMethod(Objects::class, 'generateDumperList')]
#[CoversMethod(Objects::class, 'addPropertyDumper')]
#[CoversMethod(AbstractCallback::class, 'dispatchStartEvent')]
#[CoversMethod(Objects::class, 'setParameters')]
class ObjectsTest extends AbstractHelper
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

    protected function setUp(): void
    {
        $this->fixture[CallbackConstInterface::PARAM_DATA] = new SimpleFixture();
        $this->fixture[CallbackConstInterface::PARAM_NAME] = 'some string';

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
            OpaqueRessource::class => CallbackNothing::class,
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
        $this->assertCount(3, $parameters);
        $this->assertTrue(isset($parameters[CallbackConstInterface::PARAM_DATA]));
        $this->assertTrue(isset($parameters['ref']));
        $this->assertTrue(isset($parameters[CallbackConstInterface::PARAM_NAME]));
        $this->assertEquals(
            $this->fixture[CallbackConstInterface::PARAM_DATA],
            $parameters[CallbackConstInterface::PARAM_DATA]
        );
        $this->assertEquals(
            $this->fixture[CallbackConstInterface::PARAM_NAME],
            $parameters[CallbackConstInterface::PARAM_NAME]
        );
        $this->assertTrue(is_a($parameters['ref'], ReflectionClass::class));
    }

    /**
     * Testing the start event and if other analysis classes are getting used,
     * according to the configuration.
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
     */
    public function testCallMeTraversableDeactivatedTraversable()
    {
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, false);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->fixture[CallbackConstInterface::PARAM_DATA] = new TraversableFixture();
        $this->objects->setParameters($this->fixture)->callMe();
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test with traversable deactivated and with a normal class
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
     */
    public function testCallMeTraversableActivated()
    {
        // Test with traversable activated and with a traversable class
        $this->setConfigValue(Fallback::SETTING_ANALYSE_TRAVERSABLE, true);
        Krexx::$pool->rewrite[Traversable::class] = CallbackCounter::class;
        $this->fixture[CallbackConstInterface::PARAM_DATA] = new TraversableFixture();
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the debug methods analysis is triggered.
     */
    public function testCallMeDebugMethods()
    {
        Krexx::$pool->rewrite[DebugMethods::class] = CallbackCounter::class;
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test, if the analysis of an error object works.
     */
    public function testCallMeException()
    {
        Krexx::$pool->rewrite[ErrorObject::class] = CallbackCounter::class;
        $this->fixture[CallbackConstInterface::PARAM_DATA] = new Exception('message', 123);
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test the handling of the log model.
     */
    public function testCallMeLogModel()
    {
        $logModel = new Model();
        $logModel->setCode(12345)
            ->setFile('autoexec.bat')
            ->setLine(42)
            ->setTrace(debug_backtrace())
            ->setMessage('Unit tests are fun.');
        Krexx::$pool->rewrite[ErrorObject::class] = CallbackCounter::class;
        $this->fixture[CallbackConstInterface::PARAM_DATA] = $logModel;
        $this->objects->setParameters($this->fixture)
            ->callMe();
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test the handling of incomplete objects.
     *
     * Wasn't there a Swing Out Sister song about this?
     */
    public function testCallMeIncomplete()
    {
        Krexx::$pool->rewrite[PublicProperties::class] = CallbackCounter::class;
        $this->fixture[CallbackConstInterface::PARAM_DATA] = unserialize('O:8:"Phxbject":1:{s:3:"wat";s:3:"qqq";}');

        $this->objects->setParameters($this->fixture)
            ->callMe();

        // Was it called?
        $this->assertEquals(1, CallbackCounter::$counter);
        // All parameters set?
        $this->parametersTest(CallbackCounter::$staticParameters[0]);
    }

    /**
     * Test the order of the in scope analysis.
     */
    public function testCallMeInScope()
    {
        // Test analyse getter true
        Krexx::$pool->rewrite[Getter::class] = CallbackCounter::class;
        // This one is depending on settings.
        $this->setConfigValue(Fallback::SETTING_ANALYSE_GETTER, true);
        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('isInScope')
            ->willReturn(true);

        $poolMock = $this->createMock(Pool::class);
        $poolMock->scope = $scopeMock;
        $poolMock->render = new RenderNothing(Krexx::$pool);
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects($this->once())
            ->method('dispatch')
            ->with(Objects::class . PluginConfigInterface::START_EVENT);
        $poolMock->eventService = $eventMock;
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(2))
            ->method('getSetting')
            ->with(...$this->withConsecutive(
                [Fallback::SETTING_ANALYSE_TRAVERSABLE],
                [Fallback::SETTING_ANALYSE_GETTER]
            ))->willReturnMap([
                [Fallback::SETTING_ANALYSE_TRAVERSABLE, false],
                [Fallback::SETTING_ANALYSE_GETTER, true]
            ]);
        $poolMock->config = $configMock;

        $callbackNothing = new CallbackNothing(Krexx::$pool);

        $poolMock->expects($this->exactly(9))
            ->method('createClass')
            ->with(...$this->withConsecutive(
                [PublicProperties::class],
                [ProtectedProperties::class],
                [PrivateProperties::class],
                [Getter::class],
                [OpaqueRessource::class],
                [Meta::class],
                [Constants::class],
                [Methods::class],
                [DebugMethods::class],
            ))->willReturnMap([
                [PublicProperties::class, $callbackNothing],
                [ProtectedProperties::class, $callbackNothing],
                [PrivateProperties::class, $callbackNothing],
                [Getter::class, $callbackNothing],
                [OpaqueRessource::class, $callbackNothing],
                [Meta::class, $callbackNothing],
                [Constants::class, $callbackNothing],
                [Methods::class, $callbackNothing],
                [DebugMethods::class, $callbackNothing]
            ]);

        $this->objects = new Objects($poolMock);
        $this->objects->setParameters($this->fixture)->callMe();
    }
}
