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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;

class ProcessArrayTest extends AbstractHelper
{
    protected function assertResults()
    {
        $this->mockEmergencyHandler();
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('upOneNestingLevel');
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('downOneNestingLevel');


        $fixture = ['just', 'some', 'values'];
        $model = new Model(Krexx::$pool);
        $model->setData($fixture);

        $processArray = new ProcessArray(Krexx::$pool);
        $this->mockEventService(
            [ProcessArray::class . PluginConfigInterface::START_PROCESS, null, $model]
        );

        $processArray->handle($model);

        $this->assertEquals(1, CallbackCounter::$counter);
        $this->assertFalse(CallbackCounter::$staticParameters[0][CallbackCounter::PARAM_MULTILINE]);
        $this->assertEquals($fixture, CallbackCounter::$staticParameters[0][CallbackCounter::PARAM_DATA]);
        $this->assertEquals(CallbackCounter::TYPE_ARRAY, $model->getType());
        $this->assertEquals(count($fixture) . ' elements', $model->getNormal());
    }

    /**
     * Test the reading of the settings
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray::__construct
     */
    public function testConstruct()
    {
        $processArray = new ProcessArray(\Krexx::$pool);
        $this->assertNotEquals(0, $this->retrieveValueByReflection('arrayCountLimit', $processArray));
    }

    /**
     * Test the processing of a normal array.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray::handleNoneScalar
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\AbstractProcessNoneScalar::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::generateDomIdFromObject
     */
    public function testProcessNormal()
    {
        Krexx::$pool->rewrite[ThroughArray::class] = CallbackCounter::class;
        $this->assertResults();
    }

    /**
     * Test the processing of a large array.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray::handleNoneScalar
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\AbstractProcessNoneScalar::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::dispatchProcessEvent
     * @covers \Brainworxx\Krexx\Analyse\Routing\AbstractRouting::generateDomIdFromObject
     */
    public function testProcessLargeArray()
    {
        Krexx::$pool->rewrite[ThroughLargeArray::class] = CallbackCounter::class;
        Krexx::$pool->config->settings[Fallback::SETTING_ARRAY_COUNT_LIMIT]->setValue('2');
        $this->assertResults();
    }

    /**
     * Test the check if we can handle the array processing.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray::canHandle
     */
    public function testCanHandle()
    {
        $processArray = new ProcessArray(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = [];

        $this->assertTrue($processArray->canHandle($model->setData($fixture)));
        $fixture = 'abc';
        $this->assertFalse($processArray->canHandle($model->setData($fixture)));
    }
}
