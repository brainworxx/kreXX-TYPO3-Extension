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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use ReflectionMethod;

class MethodsTest extends AbstractTest
{

    const PRIVATE_METHOD = 'privateMethod';
    const PROTECTED_METHOD = 'protectedMethod';
    const PUBLIC_METHOD = 'publicMethod';
    const TROUBLESOME_METHOD = 'troublesomeMethod';
    const CLASS_METHOD = 'classMethod';

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::analysisEnd';

    /**
     * @var string
     */
    protected $recursionEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::recursion';

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
    protected function krexxUp()
    {
        parent::krexxUp();

        $this->methods = new Methods(Krexx::$pool);
        // Prevent getting deeper into the rabbit hole.
        Krexx::$pool->rewrite = [
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

        $this->methods->setParameters($this->fixture);
    }

    /**
     * Testing the methods analysis recursion.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     */
    public function testCallMeRecursion()
    {
        // Set up the recursion events
        $this->mockEventService(
            [$this->startEvent, $this->methods],
            [$this->recursionEvent, $this->methods]
        );

        // Set up the configuration.
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        $this->runAndAssertResults(
            'k1_m_',
            true,
            0,
            []
        );
    }

    /**
     * Testing the analysis for public methods only.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     *
     * @throws \ReflectionException
     */
    public function testCallMePublic()
    {
        // Set up the events
        $this->mockEventService(
            [$this->startEvent, $this->methods],
            [$this->endEvent, $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        $this->runAndAssertResults(
            'k1_m_',
            false,
            1,
            [
                0 => [
                    'data' => [
                        new ReflectionMethod($this->fixture['data'], static::CLASS_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PUBLIC_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::TROUBLESOME_METHOD),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ]
        );
    }

    /**
     * Testing the analysis for public and protected methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     *
     * @throws \ReflectionException
     */
    public function testCallMeProtected()
    {
        // Set up the events
        $this->mockEventService(
            [$this->startEvent, $this->methods],
            [$this->endEvent, $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, false);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, true);

        $this->runAndAssertResults(
            'k1_m_pro_',
            false,
            1,
            [
                0 => [
                    'data' => [
                        new ReflectionMethod($this->fixture['data'], static::CLASS_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PROTECTED_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PUBLIC_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::TROUBLESOME_METHOD),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ]
        );
    }

    /**
     * Testing the analysis for public and private methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     *
     * @throws \ReflectionException
     */
    public function testCallMePrivate()
    {
        // Set up the events
        $this->mockEventService(
            [$this->startEvent, $this->methods],
            [$this->endEvent, $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, true);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, false);

        $this->runAndAssertResults(
            'k1_m_pri_',
            false,
            1,
            [
                0 => [
                    'data' => [
                        new ReflectionMethod($this->fixture['data'], static::CLASS_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PRIVATE_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PUBLIC_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::TROUBLESOME_METHOD),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ]
        );
    }

    /**
     * Testing the analysis for public and private methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::analyseMethods
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Methods::generateDomIdFromClassname
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\AbstractObjectAnalysis::reflectionSorting
     *
     * @throws \ReflectionException
     */
    public function testCallMePrivateProtected()
    {
        // Set up the events
        $this->mockEventService(
            [$this->startEvent, $this->methods],
            [$this->endEvent, $this->methods]
        );

        // Set up the configuration
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PRIVATE_METHODS, true);
        $this->setConfigValue(Fallback::SETTING_ANALYSE_PROTECTED_METHODS, true);

        $this->runAndAssertResults(
            'k1_m_pro_pri_',
            false,
            1,
            [
                0 => [
                    'data' => [
                        new ReflectionMethod($this->fixture['data'], static::CLASS_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PRIVATE_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PROTECTED_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::PUBLIC_METHOD),
                        new ReflectionMethod($this->fixture['data'], static::TROUBLESOME_METHOD),
                    ],
                    'ref' => $this->fixture['ref']
                ]
            ]
        );
    }

    /**
     * @param string $metaHiveKey
     * @param bool $isInHive
     * @param int $counter
     * @param array $expectation
     */
    protected function runAndAssertResults(
        string $metaHiveKey,
        bool $isInHive,
        int $counter,
        array $expectation
    ) {
        // Mock the recursion handler.
        $recursionMock = $this->createMock(Recursion::class);
        $recursionMock->expects($this->once())
            ->method('isInMetaHive')
            ->with($metaHiveKey . $this->md5Hash)
            ->will($this->returnValue($isInHive));
        // Inject it.
        Krexx::$pool->recursionHandler = $recursionMock;

        // Run the test.
        $this->methods->callMe();

        // Test the callback counter for it's parameters.
        $this->assertEquals($counter, CallbackCounter::$counter);
        $this->assertEquals($expectation, CallbackCounter::$staticParameters);
    }
}
