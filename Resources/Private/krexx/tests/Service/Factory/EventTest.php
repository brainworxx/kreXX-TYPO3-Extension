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

namespace Brainworxx\Krexx\Tests\Service\Factory;

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
    /**
     * Test the setting of the pool and the retrieval of the listener.
     *
     * @covers \Brainworxx\Krexx\Service\Factory\Event::__construct
     */
    public function test__construct()
    {
        Registration::registerEvent('some event', CallbackCounter::class);
        Registration::registerEvent('some event', 'SomeClassName');
        Registration::registerEvent('another event', CallbackNothing::class);

        $event = new Event(Krexx::$pool);

        $this->assertAttributeSame(Krexx::$pool, 'pool', $event);
        $this->assertAttributeEquals(
            [
                'some event' => [CallbackCounter::class => CallbackCounter::class, 'SomeClassName' => 'SomeClassName'],
                'another event' => [CallbackNothing::class => CallbackNothing::class]
            ],
            'register',
            $event
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
            'some event' => [
                EventHandler::class => EventHandler::class
            ],
            'another event' => [
                EventHandler::class => EventHandler::class
            ]
        ];
        $this->setValueByReflection('register', $fixture, $event);

        $callback = new CallbackNothing(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $event->dispatch('some event', $callback, $model);
        $this->assertSame(Krexx::$pool, EventHandler::$pool);
        $this->assertSame($callback, EventHandler::$callback);
        $this->assertSame($model, EventHandler::$model);
        EventHandler::$pool = null;
        EventHandler::$callback = null;
        EventHandler::$model = null;

        $callback = new CallbackNothing(Krexx::$pool);
        $event->dispatch('another event', $callback);
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