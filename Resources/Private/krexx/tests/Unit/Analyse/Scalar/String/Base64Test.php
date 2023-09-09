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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\Base64;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;

class Base64Test extends AbstractTest
{
    /**
     * Its always active
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::isActive
     */
    public function testIsActive()
    {
        $this->assertTrue(Base64::isActive());
    }

    /**
     * Test the handling of a normal string and of a base64 string.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::canHandle
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::handle
     */
    public function testCanHandle()
    {
        $base64 = new Base64(Krexx::$pool);

        $fixture = 'some string';
        $this->assertFalse($base64->canHandle($fixture, new Model(Krexx::$pool)), 'Plain string.');

        $fixture = base64_encode('Creating an "excessive" long base 64 string.');
        $this->assertTrue($base64->canHandle($fixture, new Model(Krexx::$pool)), 'Long base64 string.');
    }

    /**
     * Test the handling of the json.
     *
     * @covers \Brainworxx\Krexx\Analyse\Scalar\String\Base64::handle
     */
    public function testHandle()
    {
        $base64 = new Base64(Krexx::$pool);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [Base64::class . PluginConfigInterface::START_EVENT, $base64],
            [Base64::class . '::callMe' . CallbackConstInterface::EVENT_MARKER_END, $base64]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $string = 'Just another string that we abuse for unit testing. Nothing special.';
        $encodedString = base64_encode($string);
        $model = new Model(Krexx::$pool);
        $model->setHasExtra(true)
            ->setData($encodedString);

        $base64->canHandle($encodedString, $model);
        $base64->callMe();

        $result = CallbackCounter::$staticParameters[0][Base64::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertEquals($string, $result['Decoded base64']);
        $this->assertEquals($encodedString, $result['Content']);
        $this->assertFalse($model->hasExtra());
    }
}