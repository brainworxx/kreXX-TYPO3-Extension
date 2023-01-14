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

namespace Brainworxx\Krexx\Tests\Unit\Service\Factory;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\EventHandler;

class EventTest extends AbstractTest
{
    const EVENT_ONE = 'some event';
    const EVENT_TWO = 'another event';


    /**
     * Test the setting of the pool and the retrieval of the listener.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Event::__construct
     */
    public function testConstruct()
    {
        $customEventHandler = 'SomeClassName';

        Registration::registerEvent(static::EVENT_ONE, CallbackCounter::class);
        Registration::registerEvent(static::EVENT_ONE, $customEventHandler);
        Registration::registerEvent(static::EVENT_TWO, CallbackNothing::class);

        $event = new Event(Krexx::$pool);

        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $event));
        $this->assertEquals(
            [
                static::EVENT_ONE => [
                    CallbackCounter::class => CallbackCounter::class,
                    $customEventHandler => $customEventHandler
                ],
                static::EVENT_TWO => [
                    CallbackNothing::class => CallbackNothing::class
                ]
            ],
            $event->register
        );
    }

    /**
     * Test the dispatching of events and their handler.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Event::dispatch
     */
    public function testDispatch()
    {
        $event = new Event(Krexx::$pool);
        $fixture = [
            static::EVENT_ONE => [
                EventHandler::class => EventHandler::class
            ],
            static::EVENT_TWO => [
                EventHandler::class => EventHandler::class
            ]
        ];
        $this->setValueByReflection('register', $fixture, $event);

        $callback = new CallbackNothing(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $event->dispatch(static::EVENT_ONE, $callback, $model);
        $this->assertSame(Krexx::$pool, EventHandler::$pool);
        $this->assertSame($callback, EventHandler::$callback);
        $this->assertSame($model, EventHandler::$model);
        EventHandler::$pool = null;
        EventHandler::$callback = null;
        EventHandler::$model = null;

        $callback = new CallbackNothing(Krexx::$pool);
        $event->dispatch(static::EVENT_TWO, $callback);
        $this->assertSame(Krexx::$pool, EventHandler::$pool);
        $this->assertSame($callback, EventHandler::$callback);
        $this->assertNull(EventHandler::$model);
        EventHandler::$pool = null;
        EventHandler::$callback = null;
        EventHandler::$model = null;

        $callback = new CallbackNothing(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $event->dispatch('no subscribers', $callback, $model);
        $this->assertNull(EventHandler::$pool);
        $this->assertNull(EventHandler::$callback);
        $this->assertNull(EventHandler::$model);
    }
}
