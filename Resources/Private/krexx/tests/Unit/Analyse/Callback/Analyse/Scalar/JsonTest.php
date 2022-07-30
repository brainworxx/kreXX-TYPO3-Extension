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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Json;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Krexx;
use stdClass;

class JsonTest extends AbstractTest
{
    /**
     * Test the json extension detection.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Json::isActive()
     */
    public function testIsActive()
    {
        $functionExists = $this
            ->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Scalar\\', 'function_exists');

        $functionExists->expects($this->exactly(2))
            ->willReturnCallback(function () {
                static $count = 0;
                ++$count;
                if ($count === 1) {
                    return true;
                }

                return false;
            });

        $this->assertTrue(Json::isActive());
        $this->assertFalse(Json::isActive());
    }

    /**
     * Test the json recognition.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Json::canHandle
     */
    public function testCanHandle()
    {
        $json = new Json(Krexx::$pool);

        $fixture = 'some string';
        $this->assertFalse($json->canHandle($fixture, new Model(Krexx::$pool)), 'Plain string.');

        $fixture = '{anotehr string';
        $this->assertFalse($json->canHandle($fixture, new Model(Krexx::$pool)), 'Pass first impression.');

        $fixture = '{"qwer": "asdf"}';
        $this->assertTrue($json->canHandle($fixture, new Model(Krexx::$pool)), 'A real json.');
    }

    /**
     * Test the handling of the json.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Json::handle
     */
    public function testHandle()
    {
        $json = new Json(Krexx::$pool);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [Json::class . PluginConfigInterface::START_EVENT, $json],
            [Json::class . '::callMe' . Json::EVENT_MARKER_END, $json]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $string = '{"asdf": "yxcv"}';
        $encodedString = 'meh';
        $model = new Model(Krexx::$pool);
        $model->setHasExtra(true)
            ->setData($encodedString);

        $expectation = new stdClass();
        $expectation->asdf = 'yxcv';
        $json->canHandle($string, $model);
        $json->callMe();

        $result = CallbackCounter::$staticParameters[0][Json::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertStringContainsString('asdf', $result[Json::META_PRETTY_PRINT]);
        $this->assertStringContainsString('yxcv', $result[Json::META_PRETTY_PRINT]);
        $this->assertEquals($expectation, $result[Json::META_DECODED_JSON]);
        $this->assertEquals($encodedString, $result[Json::META_CONTENT]);
        $this->assertFalse($model->hasExtra());
    }
}
