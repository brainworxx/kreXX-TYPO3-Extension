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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMeta;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Krexx;

class CallbackTest extends AbstractTest
{
    /**
     * Test if the callback analyser can identify a callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::canHandle
     */
    public function testCanHandle()
    {
        $stringCallback = new Callback(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $this->assertTrue($stringCallback->canHandle('strpos', $model), 'This ia a predefinedphp function.');
        $this->assertFalse($stringCallback->canHandle('sdfsd dsf sdf ', $model), 'Just a random string.');
    }

    /**
     * Test the analysis of a callback.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::handle
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::retrieveDeclarationPlace
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::insertParameters
     */
    public function testCallMeNormal()
    {
        $this->mockEmergencyHandler();

        // Prepare the guinea pig.
        $stringCallback = new Callback(Krexx::$pool);
        $stringCallback->canHandle('myLittleCallback', new Model(Krexx::$pool));

        // Test the calling of the events.
        $this->mockEventService(
            [Callback::class . PluginConfigInterface::START_EVENT, $stringCallback],
            [Callback::class . '::callMe' . Callback::EVENT_MARKER_END, $stringCallback]
        );

        Krexx::$pool->rewrite = [
            ThroughMeta::class => CallbackCounter::class
        ];

        $stringCallback->callMe();
        $result = CallbackCounter::$staticParameters[0][Callback::PARAM_DATA];
        $this->assertEquals(1, CallbackCounter::$counter);

        $this->assertStringStartsWith('Fixture for the callback analysis.', $result['Comment']);
        $this->assertStringContainsString(
            'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Callback.php',
            $result['Declared in']
        );
        $this->assertStringContainsString('in line: 45', $result['Declared in']);
        $this->assertEquals('string $justAString', $result['Parameter #1']);
    }

    /**
     * Test the error handling in the callMe.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Scalar\Callback::handle
     */
    public function testCallMeError()
    {
        // Create a fixture that is supposed to trigger a ReflectionException.
        $stringCallback = new Callback(Krexx::$pool);
        $fixture = [Callback::PARAM_DATA => 'dgdg dsf '];
        $stringCallback->setParameters($fixture);

        // Expect a start event. nothing more here.
        $this->mockEventService([Callback::class . PluginConfigInterface::START_EVENT, $stringCallback]);

        $stringCallback->callMe();
    }
}
