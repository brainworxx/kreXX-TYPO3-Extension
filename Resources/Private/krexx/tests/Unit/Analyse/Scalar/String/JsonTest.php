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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Scalar\String;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Scalar\String\Json;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Krexx;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Json::class, 'handle')]
#[CoversMethod(Json::class, 'canHandle')]
#[CoversMethod(Json::class, 'isActive')]
class JsonTest extends AbstractHelper
{
    /**
     * Test the json extension detection.
     */
    public function testIsActive()
    {
        $functionExists = $this
            ->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Scalar\\String\\', 'function_exists');

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
        $this->assertEquals($fixture, $this->retrieveValueByReflection('handledValue', $json));
    }

    /**
     * Test the handling of the json.
     */
    public function testHandle()
    {
        $json = new Json(Krexx::$pool);

        $this->mockEmergencyHandler();
        $this->mockEventService(
            [Json::class . PluginConfigInterface::START_EVENT, $json],
            [Json::class . '::callMe' . CallbackConstInterface::EVENT_MARKER_END, $json]
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
        $this->assertStringContainsString('asdf', $result['Pretty print']);
        $this->assertStringContainsString('yxcv', $result['Pretty print']);
        $this->assertEquals($expectation, $result['Decoded json']);
        $this->assertEquals($encodedString, $result['Content']);
        $this->assertFalse($model->hasExtra());
    }
}
