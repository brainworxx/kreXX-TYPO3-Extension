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

namespace Brainworxx\Krexx\Tests\Helpers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Unit\KrexxTest;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Krexx;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use RuntimeException;

abstract class AbstractHelper extends TestCase
{
    use PHPMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->mockPhpSapiNameStandard();
        Pool::createPool();
    }

    /**
     * @throws \ReflectionException
     */
    protected function tearDown(): void
    {
        // Reset the kreXX count.
        $emergencyRef = new \ReflectionClass(Krexx::$pool->emergencyHandler);
        $krexxCountRef = $emergencyRef->getProperty(KrexxTest::KREXX_COUNT);
        $krexxCountRef->setAccessible(true);
        $krexxCountRef->setValue(Krexx::$pool->emergencyHandler, 0);

        // Reset the messages.
        $messageRef = new \ReflectionClass(Krexx::$pool->messages);
        $keysRef = $messageRef->getProperty('messages');
        $keysRef->setAccessible(true);
        $keysRef->setValue(Krexx::$pool->messages, []);

        // Remove possible logfiles.
        if (strpos(Krexx::$pool->config->getLogDir(), 'Fixtures') === false) {
            $logList = glob(Krexx::$pool->config->getLogDir() . '*.Krexx.html');
            if (!empty($logList)) {
                foreach ($logList as $file) {
                    unlink($file);
                    unlink($file . '.json');
                }
            }
        }

        // Reset the pool and the settings.
        AbstractController::$analysisInProgress = false;
        Krexx::$pool = null;
        Config::$disabledByPhp = false;
        CallbackCounter::$counter = 0;
        CallbackCounter::$staticParameters = [];

        // Reset stuff from the plugins.
        $this->setValueByReflection('logFolder', '', Registration::class);
        $this->setValueByReflection('chunkFolder', '', Registration::class);
        $this->setValueByReflection('configFile', '', Registration::class);
        $this->setValueByReflection('blacklistDebugMethods', [], Registration::class);
        $this->setValueByReflection('blacklistDebugClass', [], Registration::class);
        $this->setValueByReflection('additionalHelpFiles', [], Registration::class);
        $this->setValueByReflection('eventList', [], Registration::class);
        $this->setValueByReflection('rewriteList', [], Registration::class);
        $this->setValueByReflection('additionalSkinList', [], Registration::class);
        $this->setValueByReflection('plugins', [], Registration::class);
        $this->setValueByReflection('additionalScalarString', [], Registration::class);
        $this->setValueByReflection('newFallbackValues', [], Registration::class);
        $this->setValueByReflection('additionalLanguages', [], Registration::class);
        $this->setValueByReflection('newSettings', [], Registration::class);
    }

    /**
     * Setting a protected value in the class we are testing.
     *
     * @param string $name
     *   The name of the value.
     * @param mixed $value
     *   The value we want to set.
     * @param object|string $object
     *   The instance where we want to set the value. Or the class name, when
     *   setting static values.
     */
    protected function setValueByReflection(string $name, $value, $object)
    {
        try {
            $reflectionClass = new \ReflectionClass($object);
            $reflectionProperty = $reflectionClass->getProperty($name);
            $reflectionProperty->setAccessible(true);

            if (is_object($object)) {
                $reflectionProperty->setValue($object, $value);
            } else {
                if (version_compare(phpversion(), '8.3.0', '>=')) {
                    $reflectionClass->setStaticPropertyValue($name, $value);
                } else {
                    $reflectionProperty->setValue($value);
                }
            }
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Getting a protected/private value by reflection.
     *
     * @param string $name
     *   The name of the property.
     * @param object|string $object
     *   The instance from where we want to get the value. Or the class name,
     *   when getting static values.
     *
     * @return mixed
     *   The value.
     */
    protected function retrieveValueByReflection($name, $object)
    {
        try {
            $reflectionClass = new \ReflectionClass($object);
            $reflectionProperty = $reflectionClass->getProperty($name);
            $reflectionProperty->setAccessible(true);
            if (is_object($object)) {
                return $reflectionProperty->getValue($object);
            } else {
                return $reflectionProperty->getValue();
            }
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
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
        $invocationMocker->with(...$this->withConsecutive(...$eventList));

        // Inject the mock.
        Krexx::$pool->eventService = $eventServiceMock;
    }

    /**
     * Standard mocking of the debug_backtrace.
     */
    protected function mockDebugBacktraceStandard()
    {
        $fixture = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [
                BacktraceConstInterface::TRACE_FUNCTION => 'krexx',
                BacktraceConstInterface::TRACE_CLASS => 'MockClass',
                BacktraceConstInterface::TRACE_FILE => 'mockfile.php',
                BacktraceConstInterface::TRACE_LINE => 999
            ]
        ];

        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller\\', 'debug_backtrace');
        $debugBacktrace->expects($this->once())
            ->willReturn($fixture);
    }

    /**
     * Standard mocking of the php_sapi_name to prevent cli detection.
     */
    protected function mockPhpSapiNameStandard()
    {
        $phpSapiNameMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\View\\Output\\', 'php_sapi_name');
        $phpSapiNameMock->expects($this->any())
            ->will(
                $this->returnValue('whatever')
            );
    }

    /**
     * Trigger the start event in a class object, without the actual hostig
     * object interference.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback $object
     *   The object, triggering the event.
     *
     * @return string
     *   The rendered html output.
     */
    protected function triggerStartEvent(AbstractCallback $object)
    {
        try {
            $reflection = new \ReflectionClass($object);
            $reflectionMethod = $reflection->getMethod('dispatchStartEvent');
            $reflectionMethod->setAccessible(true);
            return $reflectionMethod->invoke($object);
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Usage: ->with(...$this->withConsecutive(...$withCodes))
     *
     * @see https://gist.github.com/oleg-andreyev/85c74dbf022237b03825c7e9f4439303
     *   (c) by Oleg Andreyev
     *
     * @param array $parameterGroups
     *
     * @return array
     */
    public function withConsecutive(...$parameterGroups): array
    {
        $result = [];
        $parametersCount = null;
        $groups = [];
        $values = [];

        foreach ($parameterGroups as $index => $parameters) {
            // initial
            $parametersCount = $parametersCount ?? count($parameters);

            // prepare parameters
            foreach ($parameters as $parameter) {
                if (!$parameter instanceof Constraint) {
                    $parameter = new IsEqual($parameter);
                }

                $groups[$index][] = $parameter;
            }
        }

        // collect values
        foreach ($groups as $parameters) {
            foreach ($parameters as $index => $parameter) {
                $values[$index][] = $parameter;
            }
        }

        // build callback
        for ($index = 0; $index < $parametersCount; ++$index) {
            $result[$index] = Assert::callback(static function ($value) use ($values, $index) {
                static $map = null;
                $map = $map ?? $values[$index];

                $expectedArg = array_shift($map);
                if ($expectedArg === null) {
                    throw new AssertionFailedError('No more expected calls');
                }
                $expectedArg->evaluate($value);

                return true;
            });
        }

        return $result;
    }

    /**
     * Test if the error callback was removed.
     */
    protected function assertPostConditions(): void
    {
        $handler = set_error_handler(
            function () {
            }
        );
        restore_error_handler();
        $this->assertNotSame(
            $handler,
            Krexx::$pool->retrieveErrorCallback(),
            'The error callback was not removed.'
        );
    }
}
