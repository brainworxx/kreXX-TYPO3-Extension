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

namespace Brainworxx\Krexx\Tests\Helpers;

use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\KrexxTest;
use Brainworxx\Krexx\View\Messages;
use PHPUnit\Framework\TestCase;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Krexx;

abstract class AbstractTest extends TestCase
{

    protected function setUp()
    {
        Pool::createPool();
    }

    /**
     * @throws \ReflectionException
     */
    protected function tearDown()
    {
        // Reset the kreXX count.
        $emergencyRef = new \ReflectionClass(Krexx::$pool->emergencyHandler);
        $krexxCountRef = $emergencyRef->getProperty(KrexxTest::KREXX_COUNT);
        $krexxCountRef->setAccessible(true);
        $krexxCountRef->setValue(Krexx::$pool->emergencyHandler, 0);

        // Reset the messages.
        $messageRef = new \ReflectionClass(Krexx::$pool->messages);
        $keysRef = $messageRef->getProperty('keys');
        $keysRef->setAccessible(true);
        $keysRef->setValue(Krexx::$pool->messages, []);

        // Remove possible logfiles.
        $logList = glob(Krexx::$pool->config->getLogDir() . '*.Krexx.html');
        if (!empty($logList)) {
            foreach ($logList as $file) {
                unlink($file);
                unlink($file . '.json');
            }
        }

        // Reset the pool and the settings.
        AbstractController::$analysisInProgress = false;
        Krexx::$pool->config = new Config(Krexx::$pool);
        Krexx::$pool->config->setDisabled(false);
        Krexx::$pool = null;
        Config::$disabledByPhp = false;
        $this->setValueByReflection('rewriteList', [], Registration::class);
        CallbackCounter::$counter = 0;
        CallbackCounter::$staticParameters = [];
    }

    /**
     * Setting a protected value in the class we are testing.
     *
     * @throws \ReflectionException
     *
     * @param string $name
     *   The name of the value.
     * @param mixed $value
     *   The value we want to set.
     * @param object|string $object
     *   The instance where we want to set the value. Or the class name, when
     *   setting static values.
     */
    protected function setValueByReflection($name, $value, $object)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($name);
        $reflectionProperty->setAccessible(true);
        if (is_object($object)) {
            $reflectionProperty->setValue($object, $value);
        } else {
            $reflectionProperty->setValue($value);
        }
    }

    /**
     * Getting a protected value in the class we are testing.
     *
     * @throws \ReflectionException
     *
     * @param string $name
     *   The name of the value.
     * @param object $object
     *   The instance where we want to set the value. Or the class name, when
     *   setting static values.
     *
     * @return mixed
     *   The value.
     */
    protected function getValueByReflection($name, $object)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($name);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Shortcut to adjust the configuration values.
     *
     * @param string $key
     *   The name of the value we want to set.
     * @param $value
     *   The actual value.
     */
    protected function setConfigValue($key, $value)
    {
        Krexx::$pool->config->settings[$key]->setValue($value);
    }

    /**
     * Mock the emergency handler, to prevent unwanted code execution.
     */
    protected function mockEmergencyHandler()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->any())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(false));
        $emergencyMock->expects($this->any())
            ->method('getKrexxCount')
            ->will($this->returnValue(1));
        Krexx::$pool->emergencyHandler = $emergencyMock;
    }

    /**
     * @param array ...$eventList
     *   Array with the events to listen for.
     */
    protected function mockEventService(...$eventList)
    {
        // Set up the events
        $eventServiceMock = $this->createMock(Event::class);
        $invocationMocker = $eventServiceMock->expects($this->exactly(count($eventList)))
            ->method('dispatch')
            ->will($this->returnValue(''));

        // The '...' is very useful, but having to use call_user_func_array to
        // pass it is just meh.
        call_user_func_array([$invocationMocker, 'withConsecutive'], $eventList);

        // Inject the mock.
        Krexx::$pool->eventService = $eventServiceMock;
    }
}
