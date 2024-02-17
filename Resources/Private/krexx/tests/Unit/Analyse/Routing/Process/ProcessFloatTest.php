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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;

class ProcessFloatTest extends AbstractHelper
{
    /**
     * Testing the float value processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     */
    public function testProcess()
    {
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);
        $fixture = 1.123456;
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessFloat(Krexx::$pool);
        $this->mockEventService(
            [ProcessFloat::class . PluginConfigInterface::START_PROCESS, null, $model]
        );
        $processor->handle($model);

        $this->assertEquals($fixture, $model->getData());
        $this->assertEquals($fixture, $model->getNormal());
    }

    /**
     * Testing the float value processing, with a micro time
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat::handle
     */
    public function testProcessWithMicrotime()
    {
        Krexx::$pool->render = new RenderNothing(Krexx::$pool);
        $fixture = microtime(true);
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);
        $processor = new ProcessFloat(Krexx::$pool);

        $processor->handle($model);

        $result = $model->getJson();
        $this->assertArrayHasKey('Timestamp', $result);
    }

    /**
     * Test the check if we can handle the array processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat::canHandle
     */
    public function testCanHandle()
    {
        $processor = new ProcessFloat(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = 1.234;

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
        $fixture = 'abc';
        $this->assertFalse($processor->canHandle($model->setData($fixture)));
    }
}
