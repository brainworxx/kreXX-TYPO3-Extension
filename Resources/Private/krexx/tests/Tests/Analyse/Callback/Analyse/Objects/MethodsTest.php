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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class MethodsTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods
     */
    protected $methods;

    /**
     * @var array
     */
    protected $fixture = [];

    /**
     * Md5 hash expected value for the hive test.
     *
     * @var string
     */
    protected $md5Hash = '3387cbe609e0326425dddd95c20b1447';

    /**
     * Setting up everything for the test.
     *
     * @throws \ReflectionException
     */
    public function setUp()
    {
        parent::setUp();

        $this->methods = new Methods(\Krexx::$pool);
        // Prevent getting deeper into the rabbit hole.
        \Krexx::$pool->rewrite = [
            ThroughMethods::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();

        // Setting up the fixture.
        $testClass = new MethodsFixture();
        $this->fixture = [
            'data' => $testClass,
            'name' => 'some name nobody cares about',
            'ref' => new ReflectionClass($testClass)
        ];

        $this->methods->setParams($this->fixture);
    }

    /**
     * Testing the methods analysis recursion.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     */
    public function testCallMeRecursion()
    {
        // Set up the recursion events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start', $this->methods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::recursion', $this->methods]
        );

        // Set up the configuration.
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with('k1_m_' . $this->md5Hash)
            ->will($this->returnValue(true));
        // Inject it.
        \Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // The recursion has no callback, and no parameters.
        // Hence, we test the callback counter for zero data.
        $this->assertEquals(0, CallbackCounter::$counter);
        $this->assertEquals([], CallbackCounter::$staticParameters);
    }

    /**
     * Testing the analysis for public methods only.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     */
    public function testCallMePublic()
    {
        // Set up the events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start', $this->methods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd', $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with('k1_m_' . $this->md5Hash)
            ->will($this->returnValue(false));
        // Inject it.
        \Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // Test the callback counter for it's parameters.
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals(
            [
                0 =>[
                    'data' => [
                        new \ReflectionMethod($this->fixture['data'], 'publicMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'troublesomeMethod'),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ],
            CallbackCounter::$staticParameters
        );
    }

    /**
     * Testing the analysis for public and protected methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     */
    public function testCallMeProtected()
    {
        // Set up the events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start', $this->methods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd', $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, true);

        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with('k1_m_pro_' . $this->md5Hash)
            ->will($this->returnValue(false));
        // Inject it.
        \Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // Test the callback counter for it's parameters.
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals(
            [
                0 =>[
                    'data' => [
                        new \ReflectionMethod($this->fixture['data'], 'protectedMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'publicMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'troublesomeMethod'),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ],
            CallbackCounter::$staticParameters
        );
    }

    /**
     * Testing the analysis for public and private methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     */
    public function testCallMePrivate()
    {
        // Set up the events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start', $this->methods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd', $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, true);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with('k1_m_pri_' . $this->md5Hash)
            ->will($this->returnValue(false));
        // Inject it.
        \Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // Test the callback counter for it's parameters.
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals(
            [
                0 =>[
                    'data' => [
                        new \ReflectionMethod($this->fixture['data'], 'privateMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'publicMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'troublesomeMethod'),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ],
            CallbackCounter::$staticParameters
        );
    }

    /**
     * Testing the analysis for public and private methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     */
    public function testCallMePrivateProtected()
    {
        // Set up the events
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start', $this->methods],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd', $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, true);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, true);

        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with('k1_m_pro_pri_' . $this->md5Hash)
            ->will($this->returnValue(false));
        // Inject it.
        \Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // Test the callback counter for it's parameters.
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals(
            [
                0 =>[
                    'data' => [
                        new \ReflectionMethod($this->fixture['data'], 'privateMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'protectedMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'publicMethod'),
                        new \ReflectionMethod($this->fixture['data'], 'troublesomeMethod'),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ],
            CallbackCounter::$staticParameters
        );
    }
}
