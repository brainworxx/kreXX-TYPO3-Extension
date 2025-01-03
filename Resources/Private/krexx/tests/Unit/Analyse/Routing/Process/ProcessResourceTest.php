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

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResource;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ProcessResource::class, 'canHandle')]
#[CoversMethod(ProcessResource::class, 'handle')]
#[CoversMethod(AbstractRouting::class, 'dispatchProcessEvent')]
#[CoversMethod(ProcessResource::class, 'renderUnknownOrClosed')]
#[CoversMethod(AbstractRouting::class, 'dispatchNamedEvent')]
class ProcessResourceTest extends AbstractHelper
{
    public const  PROCESS_NAMESPACE = '\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\';
    public const  GET_RESOURCE_TYPE = 'get_resource_type';
    public const  CURL_GETINFO = 'curl_getinfo';
    public const  GET_TYPE = 'gettype';

    /**
     * Testing the processing of a stream resource.
     */
    public function testProcessStream()
    {
        $this->mockEmergencyHandler();

        $resource = 'I\'m the best resource ever. Trust me, only I can do this.';
        $metaResults = [
            'best', 'stream', 'resource', 'ever'
        ];
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::GET_RESOURCE_TYPE);
        $getResourceType->expects($this->once())
            ->willReturn('stream');
        $streamGetMetsData = $this->getFunctionMock(static::PROCESS_NAMESPACE, 'stream_get_meta_data');
        $streamGetMetsData->expects($this->once())
            ->willReturn($metaResults);

        $this->runTheTest($resource, 1, 'Resource (stream)', null, $metaResults);
    }

    /**
     * Testing the processing of a curl resource.
     */
    public function testProcessCurl()
    {
        $this->mockEmergencyHandler();

        $resource = 'I am not a string.';
        $metaResults = [
            'everybody', 'likes', 'curling'
        ];
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::GET_RESOURCE_TYPE);
        $getResourceType->expects($this->once())
            ->willReturn('curl');
        $getCurlInfo = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::CURL_GETINFO);
        $getCurlInfo->expects($this->once())
            ->willReturn($metaResults);

        $this->runTheTest($resource, 1, 'Resource (curl)', null, $metaResults);
    }

    /**
     * Testing the processing of a not yet implemented resource type analysis.
     */
    public function testProcessOther()
    {
        $this->mockEmergencyHandler();

        $resource = 'Letting a string look like a resource is easy.';
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::GET_RESOURCE_TYPE);
        $getResourceType->expects($this->once())
            ->willReturn('whatever');
        $getType = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::GET_TYPE);
        $getType->expects($this->once())
            ->willReturn('Resource (whatever)');

        $this->runTheTest($resource, 0, 'Resource (whatever)', $resource);
    }

    /**
     * Test the processing of a shell resource.
     */
    public function testProcessShell()
    {
        $this->mockEmergencyHandler();

        $resource = 'I\'m going to an adventure';
        $metaResults = [
            'rocks', 'fall', 'everybody', 'dies'
        ];
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, static::GET_RESOURCE_TYPE);
        $getResourceType->expects($this->once())
            ->willReturn('process');
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, 'proc_get_status');
        $getResourceType->expects($this->once())
            ->willReturn($metaResults);

        $this->runTheTest($resource, 1, 'Resource (process)', null, $metaResults);
    }

    /**
     * Running the test is pretty much the same every way here.
     *
     * @param $resource
     * @param $counter
     * @param $normalExpectation
     * @param $dataExpectation
     * @param $metaResults
     */
    protected function runTheTest(
        $resource,
        $counter,
        $normalExpectation,
        $dataExpectation = null,
        $metaResults = null
    ) {
        Krexx::$pool->rewrite[ThroughResource::class] = CallbackCounter::class;
        $model = new Model(Krexx::$pool);
        $model->setData($resource);

        $processor = new ProcessResource(Krexx::$pool);
        if ($counter > 0) {
            $this->mockEventService(
                [ProcessResource::class . PluginConfigInterface::START_PROCESS, null, $model]
            );
        } else {
            $this->mockEventService(
                [ProcessResource::class . '::renderUnknownOrClosed', null, $model]
            );
        }
        $processor->canHandle($model);
        $processor->handle();

        $this->assertEquals(ProcessConstInterface::TYPE_RESOURCE, $model->getType());
        $this->assertEquals($normalExpectation, $model->getNormal());
        if (isset($dataExpectation)) {
            $this->assertEquals($dataExpectation, $model->getData());
        }
        if (isset($metaResults)) {
            $this->assertEquals($metaResults, $model->getParameters()[CallbackConstInterface::PARAM_DATA]);
        } else {
            $this->assertEmpty($model->getParameters());
        }
        $this->assertEquals($counter, CallbackCounter::$counter);
    }

    /**
     * Test the check if we can handle the array processing.
     */
    public function testCanHandle()
    {
        $processor = new ProcessResource(Krexx::$pool);
        $model = new Model(Krexx::$pool);
        $fixture = new stdClass();
        $getResourceType = $this->getFunctionMock(static::PROCESS_NAMESPACE, 'is_resource');
        $getResourceType->expects($this->once())
            ->willReturn(true);

        $this->assertTrue($processor->canHandle($model->setData($fixture)));
    }
}
