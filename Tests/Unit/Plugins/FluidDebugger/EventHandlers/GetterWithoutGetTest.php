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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\FluidDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterWithoutGet;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class GetterWithoutGetTest extends AbstractTest implements CallbackConstInterface
{
    /**
     * Test the setting of the pool.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterWithoutGet::__construct
     */
    public function testConstruct()
    {
        $getter = new GetterWithoutGet(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $getter));
    }

    /**
     * Test the removal of 'get' from the methodnames in fluid mode.
     */
    public function testHandle()
    {
        $this->mockEmergencyHandler();

        $getterFixture = new GetterFixture();
        $ref = new ReflectionClass($getterFixture);
        $fixture = [
            static::PARAM_DATA => $getterFixture,
            static::PARAM_REF => $ref,
            static::PARAM_NORMAL_GETTER => [
                $ref->getMethod('getSomething'),
                $ref->getMethod('getProtectedStuff'),
            ],
            static::PARAM_IS_GETTER => [
                $ref->getMethod('isGood'),
            ],
            static::PARAM_HAS_GETTER => [
                $ref->getMethod('hasValue'),
            ]
        ];

        // Subscribing.
        Registration::registerEvent(
            ThroughGetter::class . '::goThroughMethodList::end',
            GetterWithoutGet::class
        );
        Krexx::$pool->eventService = new Event(Krexx::$pool);

        $routing = new RoutingNothing(Krexx::$pool);
        Krexx::$pool->routing = $routing;

        // Load the fluid language files
        Registration::registerAdditionalHelpFile(KREXX_DIR . '..' .
            DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 'fluid.kreXX.ini');
        Krexx::$pool->messages->readHelpTexts();

        $throughGetter = new ThroughGetter(Krexx::$pool);
        $throughGetter->setParameters($fixture)->callMe();

        $methodName = 'Method name';
        $this->assertEquals('something', $routing->model[0]->getName());
        $this->assertEquals('getSomething()', $routing->model[0]->getJson()[$methodName]);
        // We expect the getProtectedStuff to be ignored.
        $this->assertEquals('good', $routing->model[1]->getName());
        $this->assertEquals('isGood()', $routing->model[1]->getJson()[$methodName]);
        $this->assertEquals('value', $routing->model[2]->getName());
        $this->assertEquals('hasValue()', $routing->model[2]->getJson()[$methodName]);
    }
}